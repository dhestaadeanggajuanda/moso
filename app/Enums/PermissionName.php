<?php

namespace App\Enums;

/**
 * Daftar permission granular. Pengaturan & manajemen pengguna sengaja tidak
 * diberikan ke role mana pun: hanya Super Admin (lewat Gate::before).
 */
enum PermissionName: string
{
    case ArticlesViewAny = 'articles.view_any';
    case ArticlesCreate = 'articles.create';
    case ArticlesUpdateOwn = 'articles.update_own';
    case ArticlesUpdateAny = 'articles.update_any';
    case ArticlesDeleteAny = 'articles.delete_any';
    case ArticlesReview = 'articles.review';
    case ArticlesPublish = 'articles.publish';
    /** Menerbitkan artikel milik sendiri (peran Penulis, hanya di alur Sederhana). */
    case ArticlesPublishOwn = 'articles.publish_own';

    case CategoriesManage = 'categories.manage';
    case PagesManage = 'pages.manage';
    case AgendasManage = 'agendas.manage';
    case MediaUpload = 'media.upload';

    case PpidManage = 'ppid.manage';
    case ServicesManage = 'services.manage';
    case SakipManage = 'sakip.manage';
    case MenusManage = 'menus.manage';

    case SettingsManage = 'settings.manage';
    case UsersManage = 'users.manage';

    /**
     * Permission bawaan setiap role (Super Admin tidak perlu, karena Gate::before).
     *
     * @return array<string, list<self>>
     */
    public static function defaultsByRole(): array
    {
        $editorial = [
            self::ArticlesViewAny,
            self::ArticlesCreate,
            self::ArticlesUpdateAny,
            self::ArticlesDeleteAny,
            self::CategoriesManage,
            self::PagesManage,
            self::AgendasManage,
            self::MediaUpload,
            self::PpidManage,
            self::ServicesManage,
            self::SakipManage,
        ];

        return [
            RoleName::Approver->value => [...$editorial, self::ArticlesPublish, self::MenusManage],
            RoleName::Editor->value => [...$editorial, self::ArticlesReview, self::MenusManage],
            RoleName::Penulis->value => [
                self::ArticlesViewAny,
                self::ArticlesCreate,
                self::ArticlesUpdateOwn,
                self::ArticlesPublishOwn,
                self::MediaUpload,
            ],
            RoleName::Kontributor->value => [
                self::ArticlesViewAny,
                self::ArticlesCreate,
                self::ArticlesUpdateOwn,
                self::MediaUpload,
            ],
        ];
    }
}
