<?php

namespace App\Http\Controllers;

use App\Models\PaymentGatewayTest;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use App\Services\PaymentTestService;
use Illuminate\Support\Facades\Log;

class TestPaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentTestService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function getGateway(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0.01'
            ]);

            $gateway = $this->paymentService->findAvailableGateway($request->amount);

            if (!$gateway) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có cổng thanh toán khả dụng, vui lòng thử lại sau'
                ]);
            }

            return response()->json([
                'success' => true,
                'gateway' => [
                    'id' => $gateway->id,
                    'client_id' => $gateway->sandbox_client_id,
                    'remaining_limit' => $gateway->daily_limit - $gateway->current_daily_amount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get Gateway Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin cổng thanh toán'
            ], 400);
        }
    }

    public function saveTransaction(Request $request)
    {
        try {
            $request->validate([
                'gateway_id' => 'required|exists:payment_gateways_test,id',
                'paypal_order_id' => 'required|string|unique:payment_transactions,order_id',
                'amount' => 'required|numeric|min:0.01',
                'status' => 'required|in:PENDING,COMPLETED,CANCELLED,FAILED',
                'error_code' => 'nullable|string'
            ]);

            // Kiểm tra gateway
            $gateway = PaymentGatewayTest::find($request->gateway_id);
            
            // Nếu có mã lỗi, đánh dấu gateway là không khả dụng
            if ($request->error_code) {
                $gateway->update(['is_active' => false]);
                
                // Tìm gateway mới
                $newGateway = $this->paymentService->findAvailableGateway($request->amount);
                
                if (!$newGateway) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không có cổng thanh toán khả dụng',
                        'need_new_gateway' => true
                    ]);
                }
                
                $gateway = $newGateway;
                
                return response()->json([
                    'success' => true,
                    'need_new_gateway' => true,
                    'gateway' => [
                        'id' => $gateway->id,
                        'client_id' => $gateway->sandbox_client_id,
                        'remaining_limit' => $gateway->daily_limit - $gateway->current_daily_amount
                    ]
                ]);
            }

            // Kiểm tra limit như cũ
            $remainingLimit = $gateway->daily_limit - $gateway->current_daily_amount;

            if ($remainingLimit < $request->amount) {
                $newGateway = $this->paymentService->findAvailableGateway($request->amount);

                if (!$newGateway) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không có cổng thanh toán khả dụng'
                    ]);
                }

                $gateway = $newGateway;
            }

            // Tạo transaction
            $transaction = $this->paymentService->createTransaction([
                'gateway_id' => $gateway->id,
                'paypal_order_id' => $request->paypal_order_id,
                'amount' => $request->amount,
                'status' => $request->status,
                'paypal_response' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'transaction' => $transaction,
                'gateway' => [
                    'id' => $gateway->id,
                    'remaining_limit' => $gateway->daily_limit - $gateway->current_daily_amount
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Save Transaction Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lưu thông tin giao dịch'
            ], 400);
        }
    }

    public function getGatewayStatus()
    {
        try {
            $gateways = $this->paymentService->getGatewayStatus();
            return response()->json([
                'success' => true,
                'data' => $gateways
            ]);
        } catch (\Exception $e) {
            Log::error('Get Gateway Status Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy trạng thái cổng thanh toán'
            ], 400);
        }
    }

    public function testSuccess(Request $request)
    {
        try {
            $orderId = $request->get('token');
            if (!$orderId) {
                throw new \Exception('Không tìm thấy mã đơn hàng');
            }

            // Cập nhật trạng thái giao dịch
            $transaction = PaymentTransaction::where('order_id', $orderId)->first();
            if ($transaction) {
                $transaction->update(['status' => 'COMPLETED']);
            }

            return view('payment.test-success', [
                'order_id' => $orderId
            ]);
        } catch (\Exception $e) {
            Log::error('Payment Success Handler Error: ' . $e->getMessage());
            return redirect()->route('payment.error')
                ->with('error', 'Có lỗi xảy ra khi xử lý thanh toán');
        }
    }

    public function testCancel(Request $request)
    {
        Log::info('Payment Cancelled', $request->all());
        return view('payment.test-cancel', [
            'order_id' => $request->get('token')
        ]);
    }

    public function testError()
    {
        return view('payment.test-error', [
            'error' => session('error')
        ]);
    }

    public function simulateError(Request $request)
    {
        try {
            $request->validate([
                'gateway_id' => 'required|exists:payment_gateways_test,id',
            ]);

            // Giả lập lỗi bằng cách gửi request với error_code
            $testData = [
                'gateway_id' => $request->gateway_id,
                'paypal_order_id' => 'TEST_ERROR_' . time(),
                'amount' => 10.00,
                'status' => 'FAILED',
                'error_code' => 'PAYMENT_SOURCE_DECLINED' // Mã lỗi PayPal thật
            ];

            return $this->saveTransaction(new Request($testData));

        } catch (\Exception $e) {
            Log::error('Test Error Simulation Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thực hiện test'
            ], 400);
        }
    }

    public function resetDailyLimits()
    {
        try {
            // Lấy tất cả gateway thay vì chỉ lấy những gateway hết hạn
            $gateways = PaymentGatewayTest::all();

            foreach ($gateways as $gateway) {
                $gateway->update([
                    'current_daily_amount' => 0,  // Reset số tiền đã dùng về 0
                    'last_reset_at' => now(),     // Cập nhật thời gian reset
                    'is_active' => true           // Kích hoạt lại gateway
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã reset giới hạn cho ' . $gateways->count() . ' cổng thanh toán',
                'gateways' => $this->paymentService->getGatewayStatus()
            ]);
        } catch (\Exception $e) {
            Log::error('Reset Daily Limits Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi reset giới hạn'
            ], 400);
        }
    }
}
