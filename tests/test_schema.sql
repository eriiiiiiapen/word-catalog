-- テスト用
-- サービス利用者（社内ユーザー）
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL COMMENT 'メールアドレス',
  `password` varchar(255) NOT NULL COMMENT 'パスワードハッシュ',
  `role` varchar(20) DEFAULT 'member' COMMENT '権限区分（admin/member）',
  `last_login_at` datetime DEFAULT NULL COMMENT '最終ログイン日時',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='システム利用者管理テーブル';

-- 顧客企業の定義
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '企業正式名称',
  `industry_code` varchar(10) DEFAULT NULL COMMENT '業種コード',
  `contract_status` tinyint(1) DEFAULT '0' COMMENT '契約ステータス（0:未契約, 1:契約中）',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='クライアント企業マスタ';

-- プロジェクトごとの用語集（今回のプロダクトの核となるテーブル想定）
CREATE TABLE `dictionary_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL COMMENT '所属企業ID',
  `physical_name` varchar(100) NOT NULL COMMENT 'DB物理名（カラム名など）',
  `logical_name` varchar(100) NOT NULL COMMENT 'ビジネス用語（論理名）',
  `description` text COMMENT '用語の詳細な定義・説明',
  `is_public` tinyint(1) DEFAULT '0' COMMENT '外部公開フラグ',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_entry` (`company_id`, `physical_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用語辞書エントリー';