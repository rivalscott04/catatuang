<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\PendingUpload;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class UploadController extends Controller
{
    /**
     * POST /internal/uploads/create
     * Create pending upload when user sends image
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'image_url' => 'required|url|max:500',
            'image_path' => 'nullable|string|max:500',
            'extracted_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = $this->normalizePhoneNumber($request->input('phone_number'));
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        // Cancel any existing pending uploads for this user
        PendingUpload::where('user_id', $user->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        // Create new pending upload
        $pendingUpload = PendingUpload::create([
            'user_id' => $user->id,
            'image_url' => $request->input('image_url'),
            'image_path' => $request->input('image_path'),
            'status' => 'pending',
            'extracted_data' => $request->input('extracted_data'),
            'expires_at' => now()->addMinutes(10), // TTL 10 minutes
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'pending_upload_id' => $pendingUpload->id,
                'expires_at' => $pendingUpload->expires_at->toIso8601String(),
                'response_style' => $user->response_style,
            ],
        ], 201);
    }

    /**
     * POST /internal/uploads/confirm
     * Confirm pending upload and create transaction
     */
    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'transaction_type' => 'required|in:income,expense',
            'amount' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:500',
            'tanggal' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = $this->normalizePhoneNumber($request->input('phone_number'));
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        // Get latest pending upload for this user
        $pendingUpload = PendingUpload::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$pendingUpload) {
            return $this->errorResponse(
                'No pending upload found',
                ['pending_upload' => ['Tidak ada gambar yang sedang menunggu konfirmasi']],
                404
            );
        }

        if ($pendingUpload->isExpired()) {
            $pendingUpload->update(['status' => 'expired']);
            return $this->errorResponse(
                'Pending upload expired',
                ['pending_upload' => ['Konfirmasi sudah kadaluarsa. Silakan upload ulang gambar.']],
                410
            );
        }

        $transactionType = $request->input('transaction_type');
        $amount = $request->input('amount');
        $description = $request->input('description');
        $tanggal = $request->input('tanggal') ? Carbon::parse($request->input('tanggal'))->toDateString() : today()->toDateString();

        // If amount/description not provided, try to get from extracted_data
        if (!$amount && $pendingUpload->extracted_data) {
            $amount = $pendingUpload->extracted_data['amount'] ?? null;
        }

        if (!$description && $pendingUpload->extracted_data) {
            $description = $pendingUpload->extracted_data['description'] ?? 'Upload gambar';
        }

        if (!$description) {
            $description = 'Upload gambar';
        }

        // If amount still not provided, return error
        if (!$amount) {
            return $this->errorResponse(
                'Amount required',
                ['amount' => ['Jumlah transaksi tidak ditemukan. Silakan input manual.']],
                422
            );
        }

        // Check struk limit (since this is from receipt/image)
        $canUseStruk = $user->canUseStruk();
        if (!$canUseStruk['allowed']) {
            return response()->json([
                'success' => false,
                'message' => 'Struk limit exceeded',
                'errors' => [
                    'struk_limit' => [
                        'Limit struk bulanan sudah tercapai. Limit: ' . $canUseStruk['limit'] . ', Terpakai: ' . $canUseStruk['used'],
                    ],
                ],
                'data' => [
                    'phone_number' => $user->phone_number,
                    'plan' => $user->plan,
                    'limit_exceeded' => true,
                    'limits' => [
                        'struk' => $canUseStruk,
                    ],
                ],
            ], 429);
        }

        DB::transaction(function () use ($user, $pendingUpload, $transactionType, $amount, $description, $tanggal) {
            // Create transaction
            Transaction::create([
                'user_id' => $user->id,
                'tanggal' => $tanggal,
                'amount' => $amount,
                'description' => $description,
                'type' => $transactionType,
                'source' => 'receipt', // Image upload = receipt
            ]);

            // Update pending upload status
            $pendingUpload->update(['status' => 'confirmed']);

            // Increment struk count
            $user->incrementStruk();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Transaksi berhasil dibuat',
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'description' => $description,
                'tanggal' => $tanggal,
            ],
        ], 201);
    }

    /**
     * GET /internal/uploads/pending
     * Get pending upload for user
     */
    public function getPending(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = $this->normalizePhoneNumber($request->input('phone_number'));
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        $pendingUpload = PendingUpload::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$pendingUpload) {
            return response()->json([
                'success' => false,
                'message' => 'No pending upload',
                'data' => [
                    'has_pending' => false,
                ],
            ], 404);
        }

        if ($pendingUpload->isExpired()) {
            $pendingUpload->update(['status' => 'expired']);
            return response()->json([
                'success' => false,
                'message' => 'Pending upload expired',
                'data' => [
                    'has_pending' => false,
                    'expired' => true,
                ],
            ], 410);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pending_upload_id' => $pendingUpload->id,
                'image_url' => $pendingUpload->image_url,
                'extracted_data' => $pendingUpload->extracted_data,
                'expires_at' => $pendingUpload->expires_at->toIso8601String(),
                'response_style' => $user->response_style,
            ],
        ]);
    }

    /**
     * Normalize phone number
     */
    private function normalizePhoneNumber(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Error response helper
     */
    private function errorResponse(string $message, $errors, int $code): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}


