<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * POST /internal/reports/pdf-expense-monthly
     * Generate PDF report for expense transactions
     */
    public function generateExpenseMonthlyPdf(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'period' => 'required|string|in:this_month,last_month,this_year',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = $this->normalizePhoneNumber($request->input('phone_number'));
        $period = $request->input('period');

        // Get user
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        // Check if user can generate report
        $canGenerate = $user->canGenerateReport();

        if (!$canGenerate['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $canGenerate['message'],
                'errors' => [
                    $canGenerate['reason'] => [$canGenerate['message']],
                ],
                'data' => [
                    'current_plan' => $user->plan,
                    'required_plans' => $canGenerate['required_plans'] ?? null,
                    'response_style' => $user->response_style,
                    'reason' => $canGenerate['reason'],
                ],
            ], 403);
        }

        // Get period dates
        $periodData = $this->getPeriodDates($period);

        // Query transactions
        $transactions = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('tanggal', [$periodData['start'], $periodData['end']])
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate total
        $totalExpense = $transactions->sum('amount');

        // Generate PDF
        try {
            $pdf = Pdf::loadView('reports.expense', [
                'user' => $user,
                'transactions' => $transactions,
                'totalExpense' => $totalExpense,
                'periodLabel' => $periodData['label'],
                'startDate' => Carbon::parse($periodData['start'])->format('d M Y'),
                'endDate' => Carbon::parse($periodData['end'])->format('d M Y'),
            ]);

            // Generate filename
            $filename = 'laporan_pengeluaran_' . strtolower(str_replace(' ', '_', $periodData['label'])) . '.pdf';

            // Convert to base64
            $pdfBase64 = base64_encode($pdf->output());

            return response()->json([
                'success' => true,
                'data' => [
                    'pdf_base64' => $pdfBase64,
                    'filename' => $filename,
                    'period' => $period,
                    'period_label' => $periodData['label'],
                    'total_expense' => $totalExpense,
                    'transaction_count' => $transactions->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to generate PDF',
                ['pdf_generation' => ['Error: ' . $e->getMessage()]],
                500
            );
        }
    }

    /**
     * Get period dates based on period type
     */
    private function getPeriodDates(string $period): array
    {
        $now = Carbon::now(config('app.timezone'));

        return match ($period) {
            'this_month' => [
                'start' => $now->copy()->startOfMonth()->toDateString(),
                'end' => $now->copy()->endOfMonth()->toDateString(),
                'label' => $now->locale('id')->isoFormat('MMMM YYYY'), // "Januari 2024"
            ],
            'last_month' => [
                'start' => $now->copy()->subMonth()->startOfMonth()->toDateString(),
                'end' => $now->copy()->subMonth()->endOfMonth()->toDateString(),
                'label' => $now->copy()->subMonth()->locale('id')->isoFormat('MMMM YYYY'), // "Desember 2023"
            ],
            'this_year' => [
                'start' => $now->copy()->startOfYear()->toDateString(),
                'end' => $now->copy()->endOfYear()->toDateString(),
                'label' => $now->format('Y'), // "2024"
            ],
            default => throw new \InvalidArgumentException('Invalid period: ' . $period),
        };
    }

    /**
     * Normalize phone number (remove + and spaces)
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

