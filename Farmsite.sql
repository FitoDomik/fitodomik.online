SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `alarm_thresholds` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `parameter_type` enum('temperature','humidity_air','humidity_soil','co2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_limit` decimal(8,2) NOT NULL,
  `max_limit` decimal(8,2) NOT NULL,
  `target_value` decimal(8,2) DEFAULT NULL COMMENT 'Целевое значение (если необходимо)',
  `tolerance` decimal(5,2) DEFAULT '1.00' COMMENT 'Допустимое отклонение',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_log` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `event_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `farm_status` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `light_level` float DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `photo_analysis` varchar(255) DEFAULT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `favorite_modes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `preset_mode_id` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `text` text NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `analysis_image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `planting_events` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` varchar(20) NOT NULL,
  `plant_name` varchar(100) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `planting_reminders` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `reminder_date` date NOT NULL,
  `reminder_time` time DEFAULT NULL,
  `is_shown` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `preset_modes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `temperature` decimal(4,1) NOT NULL,
  `tolerance` decimal(2,1) NOT NULL DEFAULT '1.0',
  `humidity` int NOT NULL,
  `humidity_tolerance` decimal(2,1) NOT NULL DEFAULT '1.0',
  `light_hours` decimal(3,1) NOT NULL,
  `light_start` time NOT NULL,
  `light_end` time NOT NULL,
  `is_shared` tinyint(1) NOT NULL DEFAULT '0',
  `share_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DELIMITER $$
CREATE TRIGGER `generate_share_code` BEFORE UPDATE ON `preset_modes` FOR EACH ROW BEGIN
  IF NEW.is_shared = 1 AND (OLD.is_shared = 0 OR OLD.is_shared IS NULL OR OLD.share_code IS NULL) THEN
    SET @chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    SET @code = '';
    SET @i = 0;
    WHILE @i < 8 DO
      SET @code = CONCAT(@code, SUBSTRING(@chars, FLOOR(1 + RAND() * 36), 1));
      SET @i = @i + 1;
    END WHILE;
    SET NEW.share_code = @code;
  END IF;
END
$$
DELIMITER ;

CREATE TABLE `schedule` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `curtains_schedule` tinyint(1) DEFAULT '0',
  `lighting_schedule` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `time` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sensor_data` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `humidity` decimal(5,2) DEFAULT NULL,
  `co2` int NOT NULL COMMENT 'Уровень CO2',
  `soil_moisture` decimal(5,2) DEFAULT NULL,
  `light_level` decimal(8,2) DEFAULT NULL,
  `pressure` decimal(7,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Создано в',
  `curtains_state` tinyint(1) DEFAULT '0' COMMENT 'состояние занавесок (1 — открыты, 0 — закрыты)',
  `lamp_state` tinyint(1) DEFAULT '0' COMMENT 'состояние лампы (1 — включено, 0 — выключено)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `avatar` varchar(255) DEFAULT NULL,
  `telegram_username` varchar(255) DEFAULT NULL,
  `telegram_chat_id` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `api_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DELIMITER $$
CREATE TRIGGER `after_user_register` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    CALL create_user_schedule(NEW.id);
END
$$
DELIMITER ;


ALTER TABLE `alarm_thresholds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_parameter_unique` (`user_id`,`parameter_type`);

ALTER TABLE `event_log`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `preset_modes`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `event_log`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

ALTER TABLE `preset_modes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;