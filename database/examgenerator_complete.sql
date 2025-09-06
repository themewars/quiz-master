-- ExamGenerator.ai - Complete SQL Import File
-- This file includes all tables including media table for Spatie Media Library

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 20, 2025 at 10:38 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `examgenerator_ai`
--


-- --------------------------------------------------------

--
-- Drop existing tables if they exist
--

DROP TABLE IF EXISTS `question_answers`;
DROP TABLE IF EXISTS `user_exams`;
DROP TABLE IF EXISTS `answers`;
DROP TABLE IF EXISTS `questions`;
DROP TABLE IF EXISTS `exams`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `plans`;
DROP TABLE IF EXISTS `currencies`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `subscriptions`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `media`;
DROP TABLE IF EXISTS `model_has_permissions`;
DROP TABLE IF EXISTS `model_has_roles`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `role_has_permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `migrations`;

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_id` bigint UNSIGNED NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exam_description` longtext COLLATE utf8mb4_unicode_ci,
  `user_id` bigint UNSIGNED NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `type` int NOT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `diff_level` int NOT NULL,
  `exam_type` int NOT NULL,
  `max_questions` int NOT NULL,
  `unique_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `view_count` int DEFAULT '0',
  `language` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `time_configuration` tinyint(1) NOT NULL DEFAULT '0',
  `time` int NOT NULL DEFAULT '0',
  `time_type` int DEFAULT NULL,
  `exam_expiry_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint UNSIGNED NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_uuid_index` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `frequency` int NOT NULL DEFAULT '1',
  `no_of_exam` int NOT NULL,
  `price` double NOT NULL,
  `trial_days` int DEFAULT NULL,
  `assign_default` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `currency_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `exam_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_answers`
--

CREATE TABLE `question_answers` (
  `id` bigint UNSIGNED NOT NULL,
  `exam_user_id` bigint UNSIGNED NOT NULL,
  `question_id` bigint UNSIGNED NOT NULL,
  `question_title` text COLLATE utf8mb4_unicode_ci,
  `answer_id` bigint UNSIGNED DEFAULT NULL,
  `answer_title` text COLLATE utf8mb4_unicode_ci,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL,
  `app_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `facebook_url` text COLLATE utf8mb4_unicode_ci,
  `twitter_url` text COLLATE utf8mb4_unicode_ci,
  `linkedin_url` text COLLATE utf8mb4_unicode_ci,
  `instagram_url` text COLLATE utf8mb4_unicode_ci,
  `pinterest_url` text COLLATE utf8mb4_unicode_ci,
  `terms_and_condition` longtext COLLATE utf8mb4_unicode_ci,
  `privacy_policy` longtext COLLATE utf8mb4_unicode_ci,
  `cookie_policy` longtext COLLATE utf8mb4_unicode_ci,
  `open_api_key` text COLLATE utf8mb4_unicode_ci,
  `hero_sub_title` text COLLATE utf8mb4_unicode_ci,
  `hero_title` text COLLATE utf8mb4_unicode_ci,
  `hero_description` text COLLATE utf8mb4_unicode_ci,
  `default_language` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `currency_before_amount` tinyint(1) NOT NULL DEFAULT '1',
  `send_mail_verification` tinyint(1) NOT NULL DEFAULT '1',
  `seo_title` text COLLATE utf8mb4_unicode_ci,
  `seo_description` text COLLATE utf8mb4_unicode_ci,
  `seo_keywords` text COLLATE utf8mb4_unicode_ci,
  `open_ai_model` varchar(255) COLLATE utf8mb4_unicode_ci,
  `enable_captcha` tinyint(1) NOT NULL DEFAULT '0',
  `captcha_site_key` text COLLATE utf8mb4_unicode_ci,
  `captcha_secret_key` text COLLATE utf8mb4_unicode_ci,
  `enabled_captcha_in_login` tinyint(1) NOT NULL DEFAULT '0',
  `enabled_captcha_in_register` tinyint(1) NOT NULL DEFAULT '0',
  `enabled_captcha_in_exam` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_exams`
--

CREATE TABLE `user_exams` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` int DEFAULT NULL,
  `exam_id` bigint UNSIGNED NOT NULL,
  `started_at` timestamp NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `last_question_id` bigint UNSIGNED DEFAULT NULL,
  `score` int NOT NULL DEFAULT '0',
  `result` longtext COLLATE utf8mb4_unicode_ci,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Dumping data for table `currencies`
--

INSERT INTO `currencies` (`id`, `name`, `code`, `symbol`, `created_at`, `updated_at`) VALUES
(1, 'Indian Rupee', 'INR', '₹', '2025-05-20 05:07:53', '2025-05-20 05:07:53');

-- --------------------------------------------------------

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `name`, `description`, `frequency`, `no_of_exam`, `price`, `trial_days`, `assign_default`, `status`, `currency_id`, `created_at`, `updated_at`) VALUES
(1, 'Default Plan', NULL, 1, 2, 0, NULL, 1, 1, 1, '2025-05-20 05:07:53', '2025-05-20 05:07:53');

-- --------------------------------------------------------

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `app_name`, `email`, `contact`, `prefix_code`, `created_at`, `updated_at`, `facebook_url`, `twitter_url`, `linkedin_url`, `instagram_url`, `pinterest_url`, `terms_and_condition`, `privacy_policy`, `cookie_policy`, `open_api_key`, `hero_sub_title`, `hero_title`, `hero_description`, `default_language`, `currency_before_amount`, `send_mail_verification`, `seo_title`, `seo_description`, `seo_keywords`, `open_ai_model`, `enable_captcha`, `captcha_site_key`, `captcha_secret_key`, `enabled_captcha_in_login`, `enabled_captcha_in_register`, `enabled_captcha_in_exam`) VALUES
(1, 'ExamGenerator AI', 'admin@gmail.com', '', 'IN', '2025-05-20 05:07:54', '2025-05-20 05:07:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'en', 1, 1, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 0, 0);

-- --------------------------------------------------------

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '2025-05-20 05:07:54', '$2y$12$NCPsrsZwsUn7PF05ckkZTu2u.7h5myIjr6CrUgapsh4.h/YDbef3m', 1, NULL, '2025-05-20 05:07:54', '2025-05-20 05:07:54'),
(2, 'User', 'user@gmail.com', '2025-05-20 05:07:54', '$2y$12$NCPsrsZwsUn7PF05ckkZTu2u.7h5myIjr6CrUgapsh4.h/YDbef3m', 1, NULL, '2025-05-20 05:07:54', '2025-05-20 05:07:54');

-- --------------------------------------------------------

--
-- Indexes for table `answers`
--

ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `answers_question_id_foreign` (`question_id`);

-- --------------------------------------------------------

--
-- Indexes for table `categories`
--

ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

-- --------------------------------------------------------

--
-- Indexes for table `currencies`
--

ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`);

-- --------------------------------------------------------

--
-- Indexes for table `exams`
--

ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exams_user_id_foreign` (`user_id`),
  ADD KEY `exams_category_id_foreign` (`category_id`);

-- --------------------------------------------------------

--
-- Indexes for table `plans`
--

ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plans_currency_id_foreign` (`currency_id`);

-- --------------------------------------------------------

--
-- Indexes for table `questions`
--

ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `questions_exam_id_foreign` (`exam_id`);

-- --------------------------------------------------------

--
-- Indexes for table `question_answers`
--

ALTER TABLE `question_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_answers_exam_user_id_foreign` (`exam_user_id`),
  ADD KEY `question_answers_question_id_foreign` (`question_id`),
  ADD KEY `question_answers_answer_id_foreign` (`answer_id`);

-- --------------------------------------------------------

--
-- Indexes for table `settings`
--

ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

-- --------------------------------------------------------

--
-- Indexes for table `user_exams`
--

ALTER TABLE `user_exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_exams_exam_id_foreign` (`exam_id`),
  ADD KEY `user_exams_last_question_id_foreign` (`last_question_id`);

-- --------------------------------------------------------

--
-- Indexes for table `users`
--

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

-- --------------------------------------------------------

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `answers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `currencies`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `exams`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `plans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `questions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `question_answers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `user_exams`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- --------------------------------------------------------

--
-- Constraints for table `answers`
--

ALTER TABLE `answers`
  ADD CONSTRAINT `answers_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- --------------------------------------------------------

--
-- Constraints for table `exams`
--

ALTER TABLE `exams`
  ADD CONSTRAINT `exams_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `exams_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- --------------------------------------------------------

--
-- Constraints for table `plans`
--

ALTER TABLE `plans`
  ADD CONSTRAINT `plans_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- --------------------------------------------------------

--
-- Constraints for table `questions`
--

ALTER TABLE `questions`
  ADD CONSTRAINT `questions_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- --------------------------------------------------------

--
-- Constraints for table `question_answers`
--

ALTER TABLE `question_answers`
  ADD CONSTRAINT `question_answers_exam_user_id_foreign` FOREIGN KEY (`exam_user_id`) REFERENCES `user_exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `question_answers_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `question_answers_answer_id_foreign` FOREIGN KEY (`answer_id`) REFERENCES `answers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- --------------------------------------------------------

--
-- Constraints for table `user_exams`
--

ALTER TABLE `user_exams`
  ADD CONSTRAINT `user_exams_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_exams_last_question_id_foreign` FOREIGN KEY (`last_question_id`) REFERENCES `questions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
