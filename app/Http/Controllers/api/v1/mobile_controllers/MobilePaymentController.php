<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MobilePaymentController extends Controller
{
    public function paymentTypes()
    {
        $paymentTypes = DB::table('payment_types')
            ->where('typ_active', 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $paymentTypes,
        ]);
    }

    public function submitCashPayment(Request $request)
    {
        $request->validate([
            'svc_id' => 'required|integer',
            'spay_amount' => 'required|numeric',
            'spay_details' => 'nullable|string|max:255',
        ]);

        $service = DB::table('services')
            ->where('svc_id', $request->svc_id)
            ->where('svc_active', 1)
            ->first();

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        $paymentId = DB::table('service_payments')->insertGetId([
            'spay_uuid' => generateuuid(),
            'svc_id' => $request->svc_id,
            'typ_id' => 1,
            'spay_reference_number' => null,
            'spay_date' => now()->toDateString(),
            'spay_amount' => $request->spay_amount,
            'spay_image_proof' => null,
            'spay_details' => $request->filled('spay_details')
            ? strtoupper(trim($request->spay_details))
            : null, 
            'spay_status' => 'FOR APPROVAL',
            'spay_date_created' => now(),
            'spay_created_by' => $service->usr_id,
            'spay_active' => 1,
        ]);

        $this->createPaymentSubmittedNotification($service->usr_id);

        return response()->json([
            'success' => true,
            'message' => 'Cash payment confirmation submitted successfully.',
            'payment_id' => $paymentId,
        ]);
    }

    public function submitEWalletPayment(Request $request)
    {
        $request->validate([
            'svc_id' => 'required|integer',
            'spay_amount' => 'required|numeric',
            'spay_reference_number' => 'required|string|max:100',
            'spay_image_proof' => 'required|image|mimes:jpeg,jpg,png,webp|max:8192',
            'spay_details' => 'nullable|string|max:255',
        ]);

        $service = DB::table('services')
            ->where('svc_id', $request->svc_id)
            ->where('svc_active', 1)
            ->first();

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        $fileName = null;

        if ($request->hasFile('spay_image_proof')) {
            $image = $request->file('spay_image_proof');
            $fileName = uniqid() . '_' . $image->getClientOriginalName();

            $folderPath = public_path('images/proof_payment_images');

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            $path = $folderPath . '/' . $fileName;
            $ext = strtolower($image->getClientOriginalExtension());

            if (in_array($ext, ['jpg', 'jpeg'])) {
                $source = imagecreatefromjpeg($image->getPathname());
                imagejpeg($source, $path, 75);
                imagedestroy($source);
            } elseif ($ext === 'png') {
                $source = imagecreatefrompng($image->getPathname());

                if ($source && imageistruecolor($source) === false) {
                    imagepalettetotruecolor($source);
                }

                if ($source) {
                    imagepng($source, $path, 7);
                    imagedestroy($source);
                }
            } elseif ($ext === 'webp') {
                $source = imagecreatefromwebp($image->getPathname());
                imagewebp($source, $path, 75);
                imagedestroy($source);
            }
        }

        $eWalletType = DB::table('payment_types')
            ->where('typ_name', 'E-WALLET')
            ->where('typ_active', 1)
            ->first();

        if (!$eWalletType) {
            return response()->json([
                'success' => false,
                'message' => 'E-Wallet payment type not found.',
            ], 404);
        }

        $paymentId = DB::table('service_payments')->insertGetId([
            'spay_uuid' => generateuuid(),
            'svc_id' => $request->svc_id,
            'typ_id' => $eWalletType->typ_id,
            'spay_reference_number' => $request->spay_reference_number,
            'spay_date' => now()->toDateString(),
            'spay_amount' => $request->spay_amount,
            'spay_image_proof' => $fileName,
            'spay_details' => $request->filled('spay_details')
            ? strtoupper(trim($request->spay_details))
            : null,   
            'spay_status' => 'FOR APPROVAL',
            'spay_date_created' => now(),
            'spay_created_by' => $service->usr_id,
            'spay_active' => 1,
        ]);

        $this->createPaymentSubmittedNotification($service->usr_id);

        return response()->json([
            'success' => true,
            'message' => 'E-Wallet payment submitted successfully.',
            'payment_id' => $paymentId,
        ]);
    }

    public function submitCheckPayment(Request $request)
    {
        $request->validate([
            'svc_id' => 'required|integer',
            'spay_amount' => 'required|numeric',
            'spay_reference_number' => 'required|string|max:100',
            'spay_image_proof' => 'required|image|mimes:jpeg,jpg,png,webp|max:8192',
            'spay_details' => 'nullable|string|max:255',
        ]);

        $service = DB::table('services')
            ->where('svc_id', $request->svc_id)
            ->where('svc_active', 1)
            ->first();

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        $checkType = DB::table('payment_types')
            ->where('typ_name', 'CHECK')
            ->where('typ_active', 1)
            ->first();

        if (!$checkType) {
            return response()->json([
                'success' => false,
                'message' => 'Check payment type not found.',
            ], 404);
        }

        $fileName = null;

        if ($request->hasFile('spay_image_proof')) {
            $image = $request->file('spay_image_proof');

            $fileName = uniqid() . '_' . $image->getClientOriginalName();

            $folderPath = public_path('images/proof_payment_images');

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            $path = $folderPath . '/' . $fileName;
            $ext = strtolower($image->getClientOriginalExtension());

            if (in_array($ext, ['jpg', 'jpeg'])) {
                $source = imagecreatefromjpeg($image->getPathname());

                if ($source) {
                    imagejpeg($source, $path, 75);
                    imagedestroy($source);
                }
            } elseif ($ext === 'png') {
                $source = imagecreatefrompng($image->getPathname());

                if ($source && imageistruecolor($source) === false) {
                    imagepalettetotruecolor($source);
                }

                if ($source) {
                    imagepng($source, $path, 7);
                    imagedestroy($source);
                }
            } elseif ($ext === 'webp') {
                $source = imagecreatefromwebp($image->getPathname());

                if ($source) {
                    imagewebp($source, $path, 75);
                    imagedestroy($source);
                }
            }
        }

        if (!$fileName) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to save check image.',
            ], 422);
        }

        $paymentId = DB::table('service_payments')->insertGetId([
            'spay_uuid' => generateuuid(),
            'svc_id' => $request->svc_id,
            'typ_id' => $checkType->typ_id,
            'spay_reference_number' => $request->spay_reference_number,
            'spay_date' => now()->toDateString(),
            'spay_amount' => $request->spay_amount,
            'spay_image_proof' => $fileName,
            'spay_details' => $request->filled('spay_details')
            ? strtoupper(trim($request->spay_details))
            : null,
            'spay_status' => 'FOR APPROVAL',
            'spay_date_created' => now(),
            'spay_created_by' => $service->usr_id,
            'spay_active' => 1,
        ]);

        $this->createPaymentSubmittedNotification($service->usr_id);

        return response()->json([
            'success' => true,
            'message' => 'Check payment submitted successfully.',
            'payment_id' => $paymentId,
        ]);
    }

    public function submitBankTransferPayment(Request $request)
    {
        $request->validate([
            'svc_id' => 'required|integer',
            'spay_amount' => 'required|numeric',
            'spay_reference_number' => 'required|string|max:100',
            'spay_image_proof' => 'required|image|mimes:jpeg,jpg,png,webp|max:8192',
            'spay_details' => 'nullable|string|max:255',
        ]);

        $service = DB::table('services')
            ->where('svc_id', $request->svc_id)
            ->where('svc_active', 1)
            ->first();

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        $bankTransferType = DB::table('payment_types')
            ->where('typ_name', 'BANK TRANSFER')
            ->where('typ_active', 1)
            ->first();

        if (!$bankTransferType) {
            return response()->json([
                'success' => false,
                'message' => 'Bank Transfer payment type not found.',
            ], 404);
        }

        $fileName = null;

        if ($request->hasFile('spay_image_proof')) {
            $image = $request->file('spay_image_proof');

            $fileName =
                uniqid() . '_' .
                $image->getClientOriginalName();

            $folderPath = public_path(
                'images/proof_payment_images'
            );

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            $path = $folderPath . '/' . $fileName;

            $ext = strtolower(
                $image->getClientOriginalExtension()
            );

            if (in_array($ext, ['jpg', 'jpeg'])) {
                $source = imagecreatefromjpeg(
                    $image->getPathname()
                );

                if ($source) {
                    imagejpeg($source, $path, 75);
                    imagedestroy($source);
                }
            } elseif ($ext === 'png') {
                $source = imagecreatefrompng(
                    $image->getPathname()
                );

                if (
                    $source &&
                    imageistruecolor($source) === false
                ) {
                    imagepalettetotruecolor($source);
                }

                if ($source) {
                    imagepng($source, $path, 7);
                    imagedestroy($source);
                }
            } elseif ($ext === 'webp') {
                $source = imagecreatefromwebp(
                    $image->getPathname()
                );

                if ($source) {
                    imagewebp($source, $path, 75);
                    imagedestroy($source);
                }
            }
        }

        if (!$fileName) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to save bank transfer receipt.',
            ], 422);
        }

        $paymentId =
            DB::table('service_payments')
                ->insertGetId([
                    'spay_uuid' => generateuuid(),
                    'svc_id' => $request->svc_id,
                    'typ_id' =>
                        $bankTransferType->typ_id,

                    // Preserve the exact transaction reference.
                    'spay_reference_number' =>
                        trim(
                            $request
                                ->spay_reference_number
                        ),

                    'spay_date' =>
                        now()->toDateString(),

                    'spay_amount' =>
                        $request->spay_amount,

                    'spay_image_proof' =>
                        $fileName,

                    // Descriptive text is stored in uppercase.
                    'spay_details' =>
                        $request->filled(
                            'spay_details'
                        )
                            ? strtoupper(
                                trim(
                                    $request
                                        ->spay_details
                                )
                            )
                            : null,

                    'spay_status' =>
                        'FOR APPROVAL',

                    'spay_date_created' =>
                        now(),

                    'spay_created_by' =>
                        $service->usr_id,

                    'spay_active' => 1,
                ]);

        $this->createPaymentSubmittedNotification($service->usr_id);

        return response()->json([
            'success' => true,
            'message' =>
                'Bank transfer payment submitted successfully.',
            'payment_id' => $paymentId,
        ]);
    }

    private function createPaymentSubmittedNotification($userId)
    {
        try {
            $message =
                'Your payment has been submitted for verification.';

            DB::table('message_notifications')->insert([
                'mesn_uuid' => generateuuid(),
                'usr_id' => $userId,
                'mesn_name' => strtoupper($message),
                'mesn_date_created' => now(),
                'mesn_created_by' => $userId,
                'mesn_view' => '0',
                'mesn_active' => 1,
            ]);
        } catch (\Exception $notificationException) {
            // Notification failure must not cancel a successful payment.
        }
    }
}

