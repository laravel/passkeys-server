<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Support;

/**
 * AAGUID to authenticator name mapping.
 *
 * @see https://github.com/passkeydeveloper/passkey-authenticator-aaguids
 */
enum Aaguids: string
{
    case AliasVault = 'a11a5faa-9f32-4b8c-8c5d-2f7d13e8c942';
    case GooglePasswordManager = 'ea9b8d66-4d01-1d21-3ce4-b6b48cb575d4';
    case ChromeOnMac = 'adce0002-35bc-c60a-648b-0b25f1f05503';
    case WindowsHello1 = '08987058-cadc-4b81-b6e1-30de50dcbe96';
    case WindowsHello2 = '9ddd1817-af5a-4672-a2b9-3e3dd95000a9';
    case WindowsHello3 = '6028b017-b1d4-4c02-b4b3-afcdafc96bb2';
    case ICloudKeychainManaged = 'dd4ec289-e01d-41c9-bb89-70fa845d4bf2';
    case Dashlane = '531126d6-e717-415c-9320-3d9aa6981239';
    case OnePassword = 'bada5566-a7aa-401f-bd96-45619a55120d';
    case NordPass = 'b84e4048-15dc-4dd0-8640-f4f60813c8af';
    case Keeper = '0ea242b4-43c4-4a1b-8b17-dd6d0b6baec6';
    case Sesame = '891494da-2c90-4d31-a9cd-4eab0aed1309';
    case Enpass = 'f3809540-7f14-49c1-a8b3-8f813b225541';
    case ChromiumBrowser = 'b5397666-4885-aa6b-cebf-e52262a439a2';
    case EdgeOnMac = '771b48fd-d3d4-4f74-9232-fc157ab0507a';
    case IDmelon = '39a5647e-1853-446c-a1f6-a79bae9f5bc7';
    case Bitwarden = 'd548826e-79b4-db40-a3d8-11116f7e8349';
    case ApplePasswords = 'fbfc3007-154e-4ecc-8c0b-6e020557d7bd';
    case SamsungPass = '53414d53-554e-4700-0000-000000000000';
    case ThalesBioIosSdk = '66a0ccb3-bd6a-191f-ee06-e375c50b9846';
    case ThalesBioAndroidSdk = '8836336a-f590-0921-301d-46427531eee6';
    case ThalesPinAndroidSdk = 'cd69adb5-3c7a-deb9-3177-6800ea6cb72a';
    case ThalesPinIosSdk = '17290f1e-c212-34d0-1423-365d729f09d9';
    case ProtonPass = '50726f74-6f6e-5061-7373-50726f746f6e';
    case KeePassXC = 'fdb141b2-5d84-443e-8a35-4698c205a502';
    case KeePassDX = 'eaecdef2-1c31-5634-8639-f1cbd9c00a08';
    case ToothPicPasskeyProvider = 'cc45f64e-52a2-451b-831a-4edd8022a202';
    case IPasswords = 'bfc748bb-3429-4faa-b9f9-7cfa9f3b76d0';
    case ZohoVault = 'b35a26b2-8f6e-4697-ab1d-d44db4da28c6';
    case LastPass = 'b78a0a55-6ef8-d246-a042-ba0f6d55050c';
    case Devolutions = 'de503f9c-21a4-4f76-b4b7-558eb55c6f89';
    case LogMeOnce = '22248c4c-7a12-46e2-9a41-44291b373a4d';
    case KasperskyPasswordManager = 'a10c6dd9-465e-4226-8198-c7c44b91c555';
    case PwSafe = 'd350af52-0351-4ba2-acd3-dfeeadc3f764';
    case MicrosoftPasswordManager = 'd3452668-01fd-4c12-926c-83a4204853aa';
    case Initial = '6d212b28-a2c1-4638-b375-5932070f62e9';
    case HeimlaneVault = 'd49b2120-b865-4191-8cea-be84a52b0485';

    public function label(): string
    {
        return match ($this) {
            self::AliasVault => 'AliasVault',
            self::GooglePasswordManager => 'Google Password Manager',
            self::ChromeOnMac => 'Chrome on Mac',
            self::WindowsHello1,
            self::WindowsHello2,
            self::WindowsHello3 => 'Windows Hello',
            self::ICloudKeychainManaged => 'iCloud Keychain (Managed)',
            self::Dashlane => 'Dashlane',
            self::OnePassword => '1Password',
            self::NordPass => 'NordPass',
            self::Keeper => 'Keeper',
            self::Sesame => 'Sésame',
            self::Enpass => 'Enpass',
            self::ChromiumBrowser => 'Chromium Browser',
            self::EdgeOnMac => 'Edge on Mac',
            self::IDmelon => 'IDmelon',
            self::Bitwarden => 'Bitwarden',
            self::ApplePasswords => 'Apple Passwords',
            self::SamsungPass => 'Samsung Pass',
            self::ThalesBioIosSdk => 'Thales Bio iOS SDK',
            self::ThalesBioAndroidSdk => 'Thales Bio Android SDK',
            self::ThalesPinAndroidSdk => 'Thales PIN Android SDK',
            self::ThalesPinIosSdk => 'Thales PIN iOS SDK',
            self::ProtonPass => 'Proton Pass',
            self::KeePassXC => 'KeePassXC',
            self::KeePassDX => 'KeePassDX',
            self::ToothPicPasskeyProvider => 'ToothPic Passkey Provider',
            self::IPasswords => 'iPasswords',
            self::ZohoVault => 'Zoho Vault',
            self::LastPass => 'LastPass',
            self::Devolutions => 'Devolutions',
            self::LogMeOnce => 'LogMeOnce',
            self::KasperskyPasswordManager => 'Kaspersky Password Manager',
            self::PwSafe => 'pwSafe',
            self::MicrosoftPasswordManager => 'Microsoft Password Manager',
            self::Initial => 'initial',
            self::HeimlaneVault => 'Heimlane Vault',
        };
    }

    public static function labelFor(string $aaguid): ?string
    {
        return self::tryFrom($aaguid)?->label();
    }
}
