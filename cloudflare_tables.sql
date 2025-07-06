-- Raw MySQL Queries for Cloudflare Tables
-- Run these queries directly in your MySQL database

-- 1. Cloudflare Accounts Table
CREATE TABLE `cloudflare_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Account name/label',
  `email` varchar(255) NOT NULL COMMENT 'Cloudflare email',
  `api_key` text NOT NULL COMMENT 'Encrypted API key',
  `api_token` text NULL COMMENT 'Optional API token',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `total_domains` int(11) NOT NULL DEFAULT 0,
  `notes` text NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cloudflare_accounts_email_unique` (`email`),
  KEY `cloudflare_accounts_is_active_index` (`is_active`),
  KEY `cloudflare_accounts_is_default_index` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Cloudflare Domains Table
CREATE TABLE `cloudflare_domains` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cloudflare_account_id` bigint(20) UNSIGNED NOT NULL,
  `zone_id` varchar(255) NOT NULL COMMENT 'Cloudflare Zone ID',
  `domain_name` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `nameservers` json NULL COMMENT 'Cloudflare nameservers',
  `plan_name` varchar(100) NULL,
  `plan_id` varchar(255) NULL,
  `dns_records_count` int(11) NOT NULL DEFAULT 0,
  `created_on_cloudflare` timestamp NULL DEFAULT NULL,
  `modified_on_cloudflare` timestamp NULL DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `sync_status` enum('synced', 'pending', 'failed') NOT NULL DEFAULT 'pending',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cloudflare_domains_zone_id_unique` (`zone_id`),
  UNIQUE KEY `cloudflare_domains_account_domain_unique` (`cloudflare_account_id`, `domain_name`),
  KEY `cloudflare_domains_cloudflare_account_id_foreign` (`cloudflare_account_id`),
  KEY `cloudflare_domains_status_index` (`status`),
  KEY `cloudflare_domains_sync_status_index` (`sync_status`),
  CONSTRAINT `cloudflare_domains_cloudflare_account_id_foreign` FOREIGN KEY (`cloudflare_account_id`) REFERENCES `cloudflare_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Cloudflare DNS Records Table
CREATE TABLE `cloudflare_dns_records` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cloudflare_domain_id` bigint(20) UNSIGNED NOT NULL,
  `cloudflare_record_id` varchar(255) NOT NULL COMMENT 'Cloudflare record ID',
  `zone_id` varchar(255) NOT NULL,
  `type` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `ttl` int(11) NOT NULL DEFAULT 1,
  `priority` int(11) NULL DEFAULT NULL,
  `proxied` tinyint(1) NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_on_cloudflare` timestamp NULL DEFAULT NULL,
  `modified_on_cloudflare` timestamp NULL DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `sync_status` enum('synced', 'pending', 'failed') NOT NULL DEFAULT 'synced',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cloudflare_dns_records_cloudflare_record_id_unique` (`cloudflare_record_id`),
  KEY `cloudflare_dns_records_cloudflare_domain_id_foreign` (`cloudflare_domain_id`),
  KEY `cloudflare_dns_records_zone_id_index` (`zone_id`),
  KEY `cloudflare_dns_records_type_index` (`type`),
  KEY `cloudflare_dns_records_name_index` (`name`),
  KEY `cloudflare_dns_records_sync_status_index` (`sync_status`),
  CONSTRAINT `cloudflare_dns_records_cloudflare_domain_id_foreign` FOREIGN KEY (`cloudflare_domain_id`) REFERENCES `cloudflare_domains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;