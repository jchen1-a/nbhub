-- =========================================================================
-- update_schema.sql
-- 此文件用于向 AI (CodeX) 和开发者声明系统中新增的数据库表结构
-- 涵盖了：论坛标签系统、点赞/收藏互动、系统通知机制
-- =========================================================================

-- 1. 论坛标签表 (Forum Tags)
CREATE TABLE `forum_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL, -- 用于URL，如 'buscar-equipo'
  `usage_count` int(11) DEFAULT 0, -- 统计标签热度
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 帖子与标签的关联表 (Post-Tag Relationship)
CREATE TABLE `forum_post_tags` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`post_id`, `tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 论坛帖子点赞表 (Forum Post Likes)
CREATE TABLE `forum_post_likes` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`post_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 帖子收藏/稍后阅读表 (User Bookmarks)
CREATE TABLE `user_bookmarks` (
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`, `post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 通知中心表 (Notifications)
CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL, -- 接收通知的人
  `actor_id` int(11) NOT NULL, -- 触发通知的人 (比如谁回复了你)
  `type` enum('reply', 'mention', 'like', 'system') NOT NULL,
  `target_url` varchar(255) NOT NULL, -- 点击通知跳转的链接
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;