<?php

namespace App\Enums;

/**
 * Las acciones que se registran en la auditoria.
 *
 * Son las trece del prompt maestro seccion 21, mas el fallo de acceso.
 *
 * Se registran los intentos de acceso FALLIDOS ademas de los correctos: un
 * listado que solo muestra logins exitosos no sirve para detectar que alguien
 * lleva media hora probando contrasenas. Es la razon principal por la que uno
 * mira un registro de auditoria.
 */
enum AuditAction: string
{
    case Login = 'login';
    case LoginFailed = 'login_failed';
    case Logout = 'logout';

    case PropertyCreated = 'property_created';
    case PropertyUpdated = 'property_updated';
    case PropertyDeleted = 'property_deleted';
    case PropertyStatusChanged = 'property_status_changed';

    case ImageUploaded = 'image_uploaded';
    case ImageDeleted = 'image_deleted';

    case SettingsChanged = 'settings_changed';
    case LogoChanged = 'logo_changed';
    case WhatsappChanged = 'whatsapp_changed';
    case MailChanged = 'mail_changed';

    case NewsPublished = 'news_published';
    case NewsDeleted = 'news_deleted';

    public function label(): string
    {
        return __('admin/audit.actions.'.$this->value);
    }

    /**
     * Icono de Material Symbols para el listado.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Login => 'login',
            self::LoginFailed => 'gpp_maybe',
            self::Logout => 'logout',
            self::PropertyCreated => 'add_home',
            self::PropertyUpdated => 'edit',
            self::PropertyDeleted => 'delete',
            self::PropertyStatusChanged => 'swap_horiz',
            self::ImageUploaded => 'add_photo_alternate',
            self::ImageDeleted => 'hide_image',
            self::SettingsChanged => 'settings',
            self::LogoChanged => 'image',
            self::WhatsappChanged => 'chat',
            self::MailChanged => 'mail',
            self::NewsPublished => 'article',
            self::NewsDeleted => 'delete_forever',
        };
    }

    /**
     * Color del chip. Solo se distinguen tres niveles a proposito: si todo
     * grita, nada destaca.
     */
    public function tone(): string
    {
        return match ($this) {
            self::LoginFailed, self::PropertyDeleted,
            self::ImageDeleted, self::NewsDeleted => 'danger',

            self::SettingsChanged, self::LogoChanged,
            self::WhatsappChanged, self::MailChanged => 'warning',

            default => 'neutral',
        };
    }

    /** @return list<self> */
    public static function forFilter(): array
    {
        return self::cases();
    }
}
