<?php

namespace App\Jobs;

use App\Models\Offer;
use App\Models\Phone;
use App\System\InvalidPhoneFormatException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectPhones extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, DispatchesJobs;

    private $offer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Offer $offer)
    {
        $this->offer = $offer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $phones = array_map('self::cleanPhone', $this->offer->phones);
        foreach ($phones as $phone) {
            $phoneEntry = null;
            try {
                $phoneEntry = Phone::firstOrCreate(
                    [
                        'id' => self::validatePhone($phone),
                    ]
                );
            } catch (InvalidPhoneFormatException $e) {
                $this->offer->invalidPhones()->firstOrCreate(
                    [
                        'phone' => $e->getPhone(),
                    ]
                );
                $this->release();
            }
            if ($phoneEntry instanceof Phone) {
                try {
                    $this->offer
                        ->phones()
                        ->attach($phoneEntry);
                    $this->dispatch(
                        (new UpdatePhoneOfferCount($phoneEntry))->onQueue('update_phone_offer_count')
                    );
                } catch (QueryException $e) {
                    if (23000 !== intval($e->getCode())) {
                        throw $e;
                    }
                }
            }
        }
    }

    private static function cleanPhone($phone)
    {
        return preg_replace('/[^+\d]/', '', $phone);
    }

    private static function validatePhone($phone)
    {
        if (preg_match('/^\+7\d{10}$/', $phone) or preg_match('/^\+380\d{9}$/', $phone)) {
            return $phone;
        } elseif (preg_match('/^0\d{9}$/', $phone)) {
            return "+38$phone";
        } elseif (preg_match('/^\+?(7|8)\d{10}$/', $phone)) {
            return preg_replace('/^\+?(7|8)/', '+7', $phone);
        } elseif (preg_match('/^9\d{9}$/', $phone)) {
            return "+7$phone";
        } elseif (preg_match('/^380\d{9}$/', $phone)) {
            return "+$phone";
        } else {
            throw (new InvalidPhoneFormatException())->setPhone($phone);
        }
    }

}
