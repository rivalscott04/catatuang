<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Normalize phone number to 62... format (without +)
     * SAMA DENGAN N8N WORKFLOW - konsisten untuk semua controller
     * 
     * @param string|null $phone
     * @return string|null
     */
    public static function normalize(?string $phone): ?string
    {
        if (empty($phone)) {
            return $phone;
        }

        // Remove all non-digit characters (spaces, dashes, +, etc)
        $phone = preg_replace('/[^\d]/', '', $phone);

        if (empty($phone) || strlen($phone) < 8) {
            return null;
        }

        // Filter: kalau dia kelihatan seperti LID/internal (misal 14 digit tapi bukan 62/0/8) -> anggap invalid
        // (biar chat_id/sender_id ga kepilih)
        if (
            strlen($phone) >= 12 &&
            !str_starts_with($phone, '62') &&
            !str_starts_with($phone, '0') &&
            !str_starts_with($phone, '8')
        ) {
            return null;
        }

        // Normalize to 62... format (without +)
        if (str_starts_with($phone, '62')) {
            return $phone; // Already in correct format
        }

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }

        return $phone;
    }
}

