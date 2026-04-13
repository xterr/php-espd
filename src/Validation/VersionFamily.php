<?php

declare(strict_types=1);

namespace Xterr\Espd\Validation;

use Xterr\Espd\Codelist\ProfileExecutionID;

enum VersionFamily: string
{
    case V2 = 'v2.1.1';
    case V3 = 'v3.3.0';
    case V4_0 = 'v4.0.0';
    case V4_1 = 'v4.1.0';

    public static function fromProfileExecutionID(ProfileExecutionID $id): ?self
    {
        return match ($id) {
            ProfileExecutionID::ESPD_EDMV2_0_0_REGULATED,
            ProfileExecutionID::ESPD_EDMV2_0_0_SELFCONTAINED,
            ProfileExecutionID::ESPD_EDMV2_1_0_REGULATED,
            ProfileExecutionID::ESPD_EDMV2_1_0_SELFCONTAINED,
            ProfileExecutionID::ESPD_EDMV2_1_1_BASIC,
            ProfileExecutionID::ESPD_EDMV2_1_1_EXTENDED => self::V2,

            ProfileExecutionID::ESPD_EDMV3_0_0,
            ProfileExecutionID::ESPD_EDMV3_0_1,
            ProfileExecutionID::ESPD_EDMV3_1_0,
            ProfileExecutionID::ESPD_EDMV3_2_0,
            ProfileExecutionID::ESPD_EDMV3_3_0 => self::V3,

            ProfileExecutionID::ESPD_EDMV4_0_0 => self::V4_0,
            ProfileExecutionID::ESPD_EDMV4_1_0 => self::V4_1,

            ProfileExecutionID::ESPD_EDMV1_0_2 => null,
        };
    }
}
