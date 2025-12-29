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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
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

        $phone = PhoneHelper::normalize($request->input('phone_number'));
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        // Cancel any existing pending uploads for this user
        PendingUpload::where('user_id', $user->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $imageUrl = $request->input('image_url');
        $imagePath = $request->input('image_path');
        $localImageUrl = null;

        // Download and save image from GoWA if image_url is provided
        if ($imageUrl && !$imagePath) {
            try {
                $localImagePath = $this->downloadAndSaveImage($imageUrl, $user->id);
                if ($localImagePath) {
                    $imagePath = $localImagePath;
                    // Generate public URL for the saved image
                    $localImageUrl = Storage::disk('public')->url($localImagePath);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request
                \Log::warning('Failed to download image from GoWA: ' . $e->getMessage(), [
                    'image_url' => $imageUrl,
                    'user_id' => $user->id,
                ]);
            }
        }

        // Create new pending upload
        $pendingUpload = PendingUpload::create([
            'user_id' => $user->id,
            'image_url' => $imageUrl,
            'image_path' => $imagePath,
            'status' => 'pending',
            'extracted_data' => $request->input('extracted_data'),
            'expires_at' => now()->addMinutes(10), // TTL 10 minutes
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'pending_upload_id' => $pendingUpload->id,
                'image_url' => $localImageUrl ?: $imageUrl, // Return local URL if available, otherwise original
                'image_path' => $imagePath,
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

        $phone = PhoneHelper::normalize($request->input('phone_number'));
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
     * POST /internal/uploads/download-image
     * Download image from GoWA and return local URL (without creating pending upload)
     * Used for OCR processing before deciding if confirmation is needed
     */
    public function downloadImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'image_url' => 'required|url|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = PhoneHelper::normalize($request->input('phone_number'));
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        $imageUrl = $request->input('image_url');
        $localImagePath = null;
        $localImageUrl = null;

        // Download and save image from GoWA
        try {
            $localImagePath = $this->downloadAndSaveImage($imageUrl, $user->id);
            if ($localImagePath) {
                // Generate public URL for the saved image
                $localImageUrl = Storage::disk('public')->url($localImagePath);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to download image from GoWA', [
                'image_url' => $imageUrl,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        if (!$localImageUrl) {
            // Fallback to original URL if download fails
            $localImageUrl = $imageUrl;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'image_url' => $localImageUrl, // Local URL from backend
                'image_path' => $localImagePath, // Local path for reference
                'original_url' => $imageUrl, // Original GoWA URL
            ],
        ]);
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

        $phone = PhoneHelper::normalize($request->input('phone_number'));
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

        // Return local URL if available, otherwise original URL
        $imageUrl = $pendingUpload->image_url;
        if ($pendingUpload->image_path && Storage::disk('public')->exists($pendingUpload->image_path)) {
            $imageUrl = Storage::disk('public')->url($pendingUpload->image_path);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pending_upload_id' => $pendingUpload->id,
                'image_url' => $imageUrl,
                'image_path' => $pendingUpload->image_path,
                'extracted_data' => $pendingUpload->extracted_data,
                'expires_at' => $pendingUpload->expires_at->toIso8601String(),
                'response_style' => $user->response_style,
            ],
        ]);
    }

    /**
     * Download image from GoWA and save to local storage
     * 
     * @param string $imageUrl URL dari GoWA
     * @param int $userId User ID untuk folder organization
     * @return string|null Local path jika berhasil, null jika gagal
     */
    private function downloadAndSaveImage(string $imageUrl, int $userId): ?string
    {
        try {
            // Download image from GoWA
            $response = Http::timeout(30)->get($imageUrl);
            
            if (!$response->successful()) {
                \Log::warning('Failed to download image from GoWA', [
                    'url' => $imageUrl,
                    'status' => $response->status(),
                ]);
                return null;
            }

            // Get image content
            $imageContent = $response->body();
            
            // Determine file extension from URL or content type
            $extension = 'jpg'; // default
            $contentType = $response->header('Content-Type');
            if ($contentType) {
                if (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) {
                    $extension = 'jpg';
                } elseif (str_contains($contentType, 'png')) {
                    $extension = 'png';
                } elseif (str_contains($contentType, 'webp')) {
                    $extension = 'webp';
                }
            } else {
                // Try to get from URL
                $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH));
                if (isset($pathInfo['extension'])) {
                    $extension = $pathInfo['extension'];
                }
            }

            // Generate unique filename
            $filename = 'uploads/' . $userId . '/' . uniqid('img_', true) . '.' . $extension;
            
            // Save to public storage
            Storage::disk('public')->put($filename, $imageContent);
            
            return $filename;
        } catch (\Exception $e) {
            \Log::error('Error downloading image from GoWA', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Normalize phone number
     */

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



