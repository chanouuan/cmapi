/*
 Navicat Premium Data Transfer

 Source Server         : 120.79.64.144_3306
 Source Server Type    : MySQL
 Source Server Version : 50616
 Source Host           : 120.79.64.144:3306
 Source Schema         : chemi_api_v1

 Target Server Type    : MySQL
 Target Server Version : 50616
 File Encoding         : 65001

 Date: 29/04/2019 11:03:54
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for admin_failedlogin
-- ----------------------------
DROP TABLE IF EXISTS `admin_failedlogin`;
CREATE TABLE `admin_failedlogin`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `account` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '账号',
  `login_count` tinyint(3) UNSIGNED NULL DEFAULT 0 COMMENT '登录次数',
  `update_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `account`(`account`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 15 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '错误登录次数记录表' ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of admin_failedlogin
-- ----------------------------
INSERT INTO `admin_failedlogin` VALUES (1, 'lxy', 6, 1554082546);
INSERT INTO `admin_failedlogin` VALUES (2, 'xiaocang', 3, 1553741063);
INSERT INTO `admin_failedlogin` VALUES (3, '111', 1, 1555657590);
INSERT INTO `admin_failedlogin` VALUES (4, 'chenbo', 3, 1555657576);
INSERT INTO `admin_failedlogin` VALUES (5, '18798799483', 2, 1553586613);
INSERT INTO `admin_failedlogin` VALUES (6, 'zxy', 1, 1553591067);
INSERT INTO `admin_failedlogin` VALUES (7, 'admin', 1, 1556423436);
INSERT INTO `admin_failedlogin` VALUES (8, 'quan18798799483', 2, 1554799141);
INSERT INTO `admin_failedlogin` VALUES (9, 'test', 1, 1553670039);
INSERT INTO `admin_failedlogin` VALUES (10, 'quanquan', 1, 1553928732);
INSERT INTO `admin_failedlogin` VALUES (11, '18798799482', 2, 1553706891);
INSERT INTO `admin_failedlogin` VALUES (12, 'abc123', 2, 1554721335);
INSERT INTO `admin_failedlogin` VALUES (13, 'dev', 1, 1554799789);
INSERT INTO `admin_failedlogin` VALUES (14, '1112', 1, 1555386619);

-- ----------------------------
-- Table structure for admin_permission_role
-- ----------------------------
DROP TABLE IF EXISTS `admin_permission_role`;
CREATE TABLE `admin_permission_role`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '角色ID',
  `permission_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '权限ID',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`role_id`, `permission_id`) USING BTREE,
  INDEX `role_id`(`role_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色权限关联表' ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of admin_permission_role
-- ----------------------------
INSERT INTO `admin_permission_role` VALUES (1, 1, 1);

-- ----------------------------
-- Table structure for admin_permissions
-- ----------------------------
DROP TABLE IF EXISTS `admin_permissions`;
CREATE TABLE `admin_permissions`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '权限名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '说明',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理权限' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_permissions
-- ----------------------------
INSERT INTO `admin_permissions` VALUES (1, 'ANY', '所有权限');
INSERT INTO `admin_permissions` VALUES (2, 'login', '登录操作');

-- ----------------------------
-- Table structure for admin_role_user
-- ----------------------------
DROP TABLE IF EXISTS `admin_role_user`;
CREATE TABLE `admin_role_user`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform` int(10) UNSIGNED NULL DEFAULT NULL,
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `role_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '角色ID',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`platform`, `user_id`, `role_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色权限关联表' ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of admin_role_user
-- ----------------------------
INSERT INTO `admin_role_user` VALUES (1, 1, 48225, 1);
INSERT INTO `admin_role_user` VALUES (2, 4, 37186, 1);

-- ----------------------------
-- Table structure for admin_roles
-- ----------------------------
DROP TABLE IF EXISTS `admin_roles`;
CREATE TABLE `admin_roles`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '角色名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '说明',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理角色' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_roles
-- ----------------------------
INSERT INTO `admin_roles` VALUES (1, '管理员', '管理员角色，拥有所有操作权限');

-- ----------------------------
-- Table structure for baoxian_company
-- ----------------------------
DROP TABLE IF EXISTS `baoxian_company`;
CREATE TABLE `baoxian_company`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '公司名称',
  `tel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '联系电话',
  `logo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'logo图标',
  `app_coupon` int(10) UNSIGNED NULL DEFAULT 0 COMMENT 'APP优惠方案ID',
  `agent_coupon` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '代理优惠方案ID',
  `order_type` tinyint(3) UNSIGNED NULL DEFAULT 1 COMMENT '出单类型：1 壁虎车险',
  `status` tinyint(3) UNSIGNED NULL DEFAULT 1 COMMENT '状态：0 禁用 1 启用',
  `create_time` timestamp(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4099 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '保险公司' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of baoxian_company
-- ----------------------------
INSERT INTO `baoxian_company` VALUES (1, '太平洋', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (2, '平安', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (4, '人保', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (8, '国寿财', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (16, '中华联合', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (32, '大地', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (64, '阳光', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (128, '太平保险', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (256, '华安', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (512, '天安', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (1024, '英大', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (2048, '安盛天平', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (4096, '安心', NULL, 'http://www.epicc.com.cn/images/site_logo.png', 1, 0, 1, 1, NULL, NULL);
INSERT INTO `baoxian_company` VALUES (4097, '保险1', NULL, NULL, 23, 22, 1, 1, '2019-02-27 06:29:27', NULL);
INSERT INTO `baoxian_company` VALUES (4098, '保险123', NULL, NULL, 19, 24, 1, 1, '2019-03-08 09:57:15', NULL);

-- ----------------------------
-- Table structure for baoxian_config
-- ----------------------------
DROP TABLE IF EXISTS `baoxian_config`;
CREATE TABLE `baoxian_config`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '配置名称',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '数据类型',
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '数据内容',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '描述',
  `create_time` timestamp(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 87 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of baoxian_config
-- ----------------------------
INSERT INTO `baoxian_config` VALUES (1, 'ORDER_PAGE_COUNT', 'number', '0', '订单分表计数，请勿修改！', NULL, NULL);
INSERT INTO `baoxian_config` VALUES (2, 'RECOMMEND_BASE_TAXPAY', 'number', '1', '车船税（代缴）', '2019-02-28 16:05:54', '2019-02-28 16:07:56');
INSERT INTO `baoxian_config` VALUES (5, 'RECOMMEND_BASE_DAOQIANG', 'number', '1', '盗抢险', '2019-02-28 16:05:55', '2019-02-28 16:07:57');
INSERT INTO `baoxian_config` VALUES (6, 'RECOMMEND_BASE_BUJIMIAN_DAOQIANG', 'number', '1', '不计免赔', '2019-02-28 16:05:55', '2019-03-07 17:26:47');
INSERT INTO `baoxian_config` VALUES (52, 'RECOMMEND_BASE_BUJIMIAN_ZIRAN', 'number', '1', '不计免赔', '2019-03-07 17:26:50', '2019-03-07 17:26:50');
INSERT INTO `baoxian_config` VALUES (9, 'RECOMMEND_BASE_LOSTSPECIAL', 'number', '1', '无法找到第三方特约险', '2019-02-28 16:05:56', '2019-02-28 16:07:58');
INSERT INTO `baoxian_config` VALUES (10, 'RECOMMEND_BASE_BUJIMIAN_LOSTSPECIAL', 'number', '1', '不计免赔', '2019-02-28 16:05:56', '2019-03-07 17:26:48');
INSERT INTO `baoxian_config` VALUES (24, 'RECOMMEND_BASE_SIJI', 'number', '10', '司机责任险', '2019-03-06 19:10:13', '2019-03-06 22:07:15');
INSERT INTO `baoxian_config` VALUES (42, 'RECOMMEND_BEST_SIJI', 'number', '1', '司机责任险', '2019-03-06 22:17:09', '2019-03-06 22:17:09');
INSERT INTO `baoxian_config` VALUES (12, 'RECOMMEND_HOT_CHESUN', 'number', '1', '机动车损失险', '2019-02-28 16:12:44', '2019-02-28 16:12:44');
INSERT INTO `baoxian_config` VALUES (50, 'RECOMMEND_BASE_BUJIMIAN_HUAHEN', 'number', '1', '不计免赔', '2019-03-07 17:26:49', '2019-03-07 17:26:49');
INSERT INTO `baoxian_config` VALUES (49, 'RECOMMEND_BASE_HUAHEN', 'number', '20000', '划痕险', '2019-03-07 17:26:49', '2019-03-07 17:26:49');
INSERT INTO `baoxian_config` VALUES (18, 'RECOMMEND_BEST_TAXPAY', 'number', '1', '车船税（代缴）', '2019-02-28 17:47:06', '2019-02-28 17:47:06');
INSERT INTO `baoxian_config` VALUES (19, 'RECOMMEND_BEST_CHESUN', 'number', '1', '机动车损失险', '2019-02-28 17:47:06', '2019-02-28 17:47:06');
INSERT INTO `baoxian_config` VALUES (20, 'RECOMMEND_BEST_BUJIMIAN_CHESUN', 'number', '1', '不计免赔', '2019-02-28 17:47:06', '2019-03-07 17:54:29');
INSERT INTO `baoxian_config` VALUES (21, 'RECOMMEND_BEST_DAOQIANG', 'number', '1', '盗抢险', '2019-02-28 17:47:07', '2019-02-28 17:47:07');
INSERT INTO `baoxian_config` VALUES (22, 'RECOMMEND_BEST_BUJIMIAN_DAOQIANG', 'number', '1', '不计免赔', '2019-02-28 17:47:07', '2019-03-07 17:54:29');
INSERT INTO `baoxian_config` VALUES (44, 'RECOMMEND_HOT_FORCEINSURANCE', 'number', '1', '交强险', '2019-03-07 17:26:02', '2019-03-07 17:26:02');
INSERT INTO `baoxian_config` VALUES (25, 'RECOMMEND_BASE_SANZHE', 'number', '10', '第三者责任险', '2019-03-06 19:12:42', '2019-03-06 19:12:42');
INSERT INTO `baoxian_config` VALUES (26, 'RECOMMEND_BASE_CHENGKE', 'number', '20', '乘客责任险', '2019-03-06 19:12:43', '2019-03-07 17:26:48');
INSERT INTO `baoxian_config` VALUES (27, 'RECOMMEND_BASE_BUJIMIAN_SIJI', 'number', '1', '不计免赔', '2019-03-06 21:24:21', '2019-03-07 17:26:48');
INSERT INTO `baoxian_config` VALUES (28, 'RECOMMEND_BASE_BUJIMIAN_CHENGKE', 'number', '1', '不计免赔', '2019-03-06 21:24:22', '2019-03-07 17:26:48');
INSERT INTO `baoxian_config` VALUES (29, 'RECOMMEND_BASE_SHESHUI', 'number', '1', '涉水险', '2019-03-06 21:24:22', '2019-03-06 21:24:22');
INSERT INTO `baoxian_config` VALUES (30, 'RECOMMEND_BASE_BOLI', 'number', '2', '玻璃险', '2019-03-06 21:24:22', '2019-03-06 22:07:29');
INSERT INTO `baoxian_config` VALUES (31, 'RECOMMEND_BASE_BUJIMIAN_SANZHE', 'number', '1', '不计免赔', '2019-03-06 21:47:41', '2019-03-07 17:26:47');
INSERT INTO `baoxian_config` VALUES (32, 'RECOMMEND_BASE_SANZHEHOLIDAYDOUBLE', 'number', '1', '三者节假日翻倍险', '2019-03-06 22:07:44', '2019-03-06 22:07:44');
INSERT INTO `baoxian_config` VALUES (33, 'RECOMMEND_HOT_SANZHE', 'number', '10', '第三者责任险', '2019-03-06 22:08:01', '2019-03-07 17:26:02');
INSERT INTO `baoxian_config` VALUES (51, 'RECOMMEND_BASE_ZIRAN', 'number', '1', '自燃损失险', '2019-03-07 17:26:50', '2019-03-07 17:26:50');
INSERT INTO `baoxian_config` VALUES (45, 'RECOMMEND_BASE_FORCEINSURANCE', 'number', '1', '交强险', '2019-03-07 17:26:46', '2019-03-07 17:26:46');
INSERT INTO `baoxian_config` VALUES (46, 'RECOMMEND_BASE_CHESUN', 'number', '1', '机动车损失险', '2019-03-07 17:26:46', '2019-03-07 17:26:46');
INSERT INTO `baoxian_config` VALUES (47, 'RECOMMEND_BASE_BUJIMIAN_CHESUN', 'number', '1', '不计免赔', '2019-03-07 17:26:46', '2019-03-07 17:26:46');
INSERT INTO `baoxian_config` VALUES (48, 'RECOMMEND_BASE_BUJIMIAN_SHESHUI', 'number', '1', '不计免赔', '2019-03-07 17:26:49', '2019-03-07 17:26:49');
INSERT INTO `baoxian_config` VALUES (39, 'RECOMMEND_BEST_FORCEINSURANCE', 'number', '1', '交强险', '2019-03-06 22:09:22', '2019-03-06 22:09:22');
INSERT INTO `baoxian_config` VALUES (40, 'RECOMMEND_BEST_SANZHE', 'number', '150', '第三者责任险', '2019-03-06 22:09:22', '2019-03-06 22:09:22');
INSERT INTO `baoxian_config` VALUES (41, 'RECOMMEND_BEST_BUJIMIAN_SANZHE', 'number', '1', '不计免赔', '2019-03-06 22:09:23', '2019-03-07 17:54:29');
INSERT INTO `baoxian_config` VALUES (43, 'RECOMMEND_BEST_BUJIMIAN_SIJI', 'number', '1', '不计免赔', '2019-03-06 22:17:09', '2019-03-07 17:54:30');
INSERT INTO `baoxian_config` VALUES (53, 'RECOMMEND_BASE_TAKESPECAILREPAIR', 'number', '1', '指定专修厂险', '2019-03-07 17:26:50', '2019-03-07 17:26:50');
INSERT INTO `baoxian_config` VALUES (54, 'RECOMMEND_HOT_TAXPAY', 'number', '1', '车船税（代缴）', '2019-03-07 17:33:53', '2019-03-07 17:33:53');
INSERT INTO `baoxian_config` VALUES (55, 'RECOMMEND_HOT_BUJIMIAN_CHESUN', 'number', '1', '不计免赔', '2019-03-07 17:33:53', '2019-03-07 17:34:04');
INSERT INTO `baoxian_config` VALUES (56, 'RECOMMEND_HOT_BUJIMIAN_SANZHE', 'number', '1', '不计免赔', '2019-03-07 17:33:53', '2019-03-07 17:34:04');
INSERT INTO `baoxian_config` VALUES (57, 'RECOMMEND_HOT_BUJIMIAN_DAOQIANG', 'number', '1', '不计免赔', '2019-03-07 17:33:54', '2019-03-07 17:34:05');
INSERT INTO `baoxian_config` VALUES (58, 'RECOMMEND_HOT_SIJI', 'number', '20', '司机责任险', '2019-03-07 17:33:54', '2019-03-07 17:33:54');
INSERT INTO `baoxian_config` VALUES (59, 'RECOMMEND_HOT_BUJIMIAN_SIJI', 'number', '1', '不计免赔', '2019-03-07 17:33:54', '2019-03-07 17:34:05');
INSERT INTO `baoxian_config` VALUES (60, 'RECOMMEND_HOT_CHENGKE', 'number', '20', '乘客责任险', '2019-03-07 17:33:55', '2019-03-07 17:33:55');
INSERT INTO `baoxian_config` VALUES (61, 'RECOMMEND_HOT_BUJIMIAN_CHENGKE', 'number', '1', '不计免赔', '2019-03-07 17:33:55', '2019-03-07 17:34:05');
INSERT INTO `baoxian_config` VALUES (62, 'RECOMMEND_HOT_LOSTSPECIAL', 'number', '1', '无法找到第三方特约险', '2019-03-07 17:33:55', '2019-03-07 17:33:55');
INSERT INTO `baoxian_config` VALUES (63, 'RECOMMEND_HOT_BUJIMIAN_LOSTSPECIAL', 'number', '1', '不计免赔', '2019-03-07 17:33:55', '2019-03-07 17:34:06');
INSERT INTO `baoxian_config` VALUES (64, 'RECOMMEND_HOT_SHESHUI', 'number', '1', '涉水险', '2019-03-07 17:33:56', '2019-03-07 17:33:56');
INSERT INTO `baoxian_config` VALUES (65, 'RECOMMEND_HOT_BUJIMIAN_SHESHUI', 'number', '1', '不计免赔', '2019-03-07 17:33:56', '2019-03-07 17:34:06');
INSERT INTO `baoxian_config` VALUES (66, 'RECOMMEND_HOT_BOLI', 'number', '2', '玻璃险', '2019-03-07 17:33:56', '2019-03-07 17:33:56');
INSERT INTO `baoxian_config` VALUES (67, 'RECOMMEND_HOT_HUAHEN', 'number', '20000', '划痕险', '2019-03-07 17:33:56', '2019-03-07 17:33:56');
INSERT INTO `baoxian_config` VALUES (68, 'RECOMMEND_HOT_BUJIMIAN_HUAHEN', 'number', '1', '不计免赔', '2019-03-07 17:33:56', '2019-03-07 17:34:07');
INSERT INTO `baoxian_config` VALUES (69, 'RECOMMEND_HOT_ZIRAN', 'number', '1', '自燃损失险', '2019-03-07 17:33:57', '2019-03-07 17:33:57');
INSERT INTO `baoxian_config` VALUES (70, 'RECOMMEND_HOT_BUJIMIAN_ZIRAN', 'number', '1', '不计免赔', '2019-03-07 17:33:57', '2019-03-07 17:34:07');
INSERT INTO `baoxian_config` VALUES (71, 'RECOMMEND_HOT_TAKESPECAILREPAIR', 'number', '1', '指定专修厂险', '2019-03-07 17:33:57', '2019-03-07 17:33:57');
INSERT INTO `baoxian_config` VALUES (72, 'RECOMMEND_HOT_SANZHEHOLIDAYDOUBLE', 'number', '1', '三者节假日翻倍险', '2019-03-07 17:33:57', '2019-03-07 17:33:57');
INSERT INTO `baoxian_config` VALUES (73, 'RECOMMEND_HOT_DAOQIANG', 'number', '1', '盗抢险', '2019-03-07 17:34:04', '2019-03-07 17:34:04');
INSERT INTO `baoxian_config` VALUES (74, 'RECOMMEND_BEST_CHENGKE', 'number', '10', '乘客责任险', '2019-03-07 17:36:28', '2019-03-07 17:36:28');
INSERT INTO `baoxian_config` VALUES (75, 'RECOMMEND_BEST_BUJIMIAN_CHENGKE', 'number', '1', '不计免赔', '2019-03-07 17:36:28', '2019-03-07 17:54:30');
INSERT INTO `baoxian_config` VALUES (76, 'RECOMMEND_BEST_LOSTSPECIAL', 'number', '1', '无法找到第三方特约险', '2019-03-07 17:36:28', '2019-03-07 17:36:28');
INSERT INTO `baoxian_config` VALUES (77, 'RECOMMEND_BEST_BUJIMIAN_LOSTSPECIAL', 'number', '1', '不计免赔', '2019-03-07 17:36:28', '2019-03-07 17:54:30');
INSERT INTO `baoxian_config` VALUES (78, 'RECOMMEND_BEST_SHESHUI', 'number', '1', '涉水险', '2019-03-07 17:36:29', '2019-03-07 17:36:29');
INSERT INTO `baoxian_config` VALUES (79, 'RECOMMEND_BEST_BUJIMIAN_SHESHUI', 'number', '1', '不计免赔', '2019-03-07 17:36:29', '2019-03-07 17:54:31');
INSERT INTO `baoxian_config` VALUES (80, 'RECOMMEND_BEST_BOLI', 'number', '1', '玻璃险', '2019-03-07 17:36:29', '2019-03-07 17:36:29');
INSERT INTO `baoxian_config` VALUES (81, 'RECOMMEND_BEST_HUAHEN', 'number', '20000', '划痕险', '2019-03-07 17:36:29', '2019-03-07 17:36:29');
INSERT INTO `baoxian_config` VALUES (82, 'RECOMMEND_BEST_BUJIMIAN_HUAHEN', 'number', '1', '不计免赔', '2019-03-07 17:36:29', '2019-03-07 17:54:31');
INSERT INTO `baoxian_config` VALUES (83, 'RECOMMEND_BEST_ZIRAN', 'number', '1', '自燃损失险', '2019-03-07 17:36:30', '2019-03-07 17:36:30');
INSERT INTO `baoxian_config` VALUES (84, 'RECOMMEND_BEST_BUJIMIAN_ZIRAN', 'number', '1', '不计免赔', '2019-03-07 17:36:30', '2019-03-07 17:54:32');
INSERT INTO `baoxian_config` VALUES (85, 'RECOMMEND_BEST_TAKESPECAILREPAIR', 'number', '1', '指定专修厂险', '2019-03-07 17:36:30', '2019-03-07 17:36:30');
INSERT INTO `baoxian_config` VALUES (86, 'RECOMMEND_BEST_SANZHEHOLIDAYDOUBLE', 'number', '1', '三者节假日翻倍险', '2019-03-07 17:36:30', '2019-03-07 17:36:30');

-- ----------------------------
-- Table structure for baoxian_coupon_plan
-- ----------------------------
DROP TABLE IF EXISTS `baoxian_coupon_plan`;
CREATE TABLE `baoxian_coupon_plan`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '方案名称',
  `type` tinyint(3) UNSIGNED NULL DEFAULT 1 COMMENT '方案类型：1 APP优惠 2 代理优惠',
  `main` tinyint(3) UNSIGNED NULL DEFAULT 1 COMMENT '返还内容：1 交强+商业 2 交强险 3 商业险',
  `common_rate` tinyint(3) UNSIGNED NULL DEFAULT 0 COMMENT '通用劵返还比例 0-100',
  `park_rate` tinyint(3) UNSIGNED NULL DEFAULT 0 COMMENT '停车劵返还比例 0-100',
  `maintain_rate` tinyint(3) UNSIGNED NULL DEFAULT 0 COMMENT '洗车保养劵返还比例 0-100',
  `insurance_rate` tinyint(3) UNSIGNED NULL DEFAULT 0 COMMENT '保险劵返还比例 0-100',
  `commission_rate` tinyint(3) UNSIGNED NULL DEFAULT NULL COMMENT '佣金比例 0-100',
  `create_time` timestamp(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 26 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '保险优惠劵方案' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of baoxian_coupon_plan
-- ----------------------------
INSERT INTO `baoxian_coupon_plan` VALUES (15, '优惠方案2', 1, 1, 4, 5, 6, 0, NULL, '2019-03-04 11:48:28', NULL);
INSERT INTO `baoxian_coupon_plan` VALUES (16, '方案23', 1, 2, 2, 3, 4, 0, NULL, '2019-03-04 11:50:30', NULL);
INSERT INTO `baoxian_coupon_plan` VALUES (19, '方案122', 1, 2, 3, 2, 3, 0, NULL, '2019-03-04 12:02:26', NULL);
INSERT INTO `baoxian_coupon_plan` VALUES (20, '方案22', 2, 2, 2, 3, 4, 0, NULL, '2019-03-04 13:52:11', NULL);
INSERT INTO `baoxian_coupon_plan` VALUES (21, '方案24', 2, 2, 4, 5, 2, 0, NULL, '2019-03-04 14:00:05', NULL);
INSERT INTO `baoxian_coupon_plan` VALUES (22, '代理优惠方案', 2, 2, 2, 0, 2, 0, 12, '2019-03-04 16:10:22', NULL);
INSERT INTO `baoxian_coupon_plan` VALUES (23, 'app优惠方案', 1, 2, 1, 2, 3, 0, NULL, '2019-03-04 16:11:23', NULL);
INSERT INTO `baoxian_coupon_plan` VALUES (24, '方案123', 2, 2, 0, 0, 2, 2, 2, '2019-03-08 09:36:59', NULL);
INSERT INTO `baoxian_coupon_plan` VALUES (25, '优惠方案233', 2, 2, 0, 1, 2, 1, 2, '2019-03-08 09:56:54', NULL);

-- ----------------------------
-- Table structure for baoxian_error
-- ----------------------------
DROP TABLE IF EXISTS `baoxian_error`;
CREATE TABLE `baoxian_error`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `url` varchar(800) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '请求地址',
  `files` varchar(800) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文件路径',
  `lines` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '错误行数',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '请求参数',
  `status` tinyint(3) UNSIGNED NULL DEFAULT NULL COMMENT '错误状态跟踪;1:等受理;2:已受理;3:跟进中;0:已完成',
  `create_time` timestamp(0) NULL DEFAULT NULL COMMENT '生成时间',
  `update_time` timestamp(0) NULL DEFAULT NULL COMMENT '更新时间',
  `method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '请求方法',
  `from` tinyint(3) UNSIGNED NULL DEFAULT NULL COMMENT '来源:1:安卓;2:ios;3:车秘后台',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 286 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统错误信息' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of baoxian_error
-- ----------------------------
INSERT INTO `baoxian_error` VALUES (1, '/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:33:53', '2019-02-26 03:33:53', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (2, '/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:38:19', '2019-02-26 03:38:19', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (3, '/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:40:17', '2019-02-26 03:40:17', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (4, '/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:45:51', '2019-02-26 03:45:51', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (5, '/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:47:40', '2019-02-26 03:47:40', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (6, '/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:48:19', '2019-02-26 03:48:19', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (7, '/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:50:28', '2019-02-26 03:50:28', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (8, '/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:51:07', '2019-02-26 03:51:07', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (9, '/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:51:40', '2019-02-26 03:51:40', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (10, '/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:51:54', '2019-02-26 03:51:54', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (11, '/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:52:01', '2019-02-26 03:52:01', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (12, '/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:52:36', '2019-02-26 03:52:36', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (13, '/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:53:16', '2019-02-26 03:53:16', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (14, '/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:54:49', '2019-02-26 03:54:49', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (15, '/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:55:37', '2019-02-26 03:55:37', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (16, '/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 03:58:28', '2019-02-26 03:58:28', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (17, '/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-02-26 04:02:17', '2019-02-26 04:02:17', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (18, '/api/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 05:46:19', '2019-02-26 05:46:19', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (19, '/api/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 05:46:40', '2019-02-26 05:46:40', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (20, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 05:47:52', '2019-02-26 05:47:52', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (21, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 05:51:54', '2019-02-26 05:51:54', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (22, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 05:53:34', '2019-02-26 05:53:34', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (23, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 05:54:05', '2019-02-26 05:54:05', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (24, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 05:54:44', '2019-02-26 05:54:44', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (25, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 05:54:59', '2019-02-26 05:54:59', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (26, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 05:59:52', '2019-02-26 05:59:52', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (27, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:01:45', '2019-02-26 06:01:45', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (28, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:02:46', '2019-02-26 06:02:46', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (29, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:03:05', '2019-02-26 06:03:05', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (30, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:03:58', '2019-02-26 06:03:58', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (31, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:04:21', '2019-02-26 06:04:21', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (32, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:04:34', '2019-02-26 06:04:34', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (33, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:04:44', '2019-02-26 06:04:44', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (34, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:05:07', '2019-02-26 06:05:07', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (35, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:05:38', '2019-02-26 06:05:38', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (36, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:06:04', '2019-02-26 06:06:04', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (37, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:06:13', '2019-02-26 06:06:13', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (38, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:06:55', '2019-02-26 06:06:55', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (39, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:07:09', '2019-02-26 06:07:09', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (40, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:07:27', '2019-02-26 06:07:27', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (41, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:08:00', '2019-02-26 06:08:00', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (42, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '方案名称为空', '[]', 1, '2019-02-26 06:10:42', '2019-02-26 06:10:42', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (43, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '请输入方案类型', '{\"name\":\"\\u4f18\\u60e0\\u52381\"}', 1, '2019-02-26 06:11:55', '2019-02-26 06:11:55', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (44, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'create_time\' in \'field list\' (SQL: insert into `baoxian_coupon_plan` (`name`, `type`, `main`, `create_time`) values (优惠券1, 1, 2, 2019-02-26 06:12:30))', '{\"name\":\"\\u4f18\\u60e0\\u52381\",\"type\":\"1\",\"main\":\"2\"}', 1, '2019-02-26 06:12:30', '2019-02-26 06:12:30', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (45, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ApiController.php', 33, 'Argument 1 passed to App\\Http\\Controllers\\Admin\\ApiController::_success() must be of the type array, integer given, called in /Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php on line 81', '{\"name\":\"\\u4f18\\u60e0\\u52381\",\"type\":\"1\",\"main\":\"2\"}', 1, '2019-02-26 06:14:21', '2019-02-26 06:14:21', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (46, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '通用劵返还比例必须为整数', '{\"name\":\"\\u4f18\\u60e0\\u52381\",\"type\":\"1\",\"main\":\"2\",\"common_rate\":\"s\"}', 1, '2019-02-26 06:24:04', '2019-02-26 06:24:04', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (47, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 78, '通用劵返还比例必须为0-100的整数', '{\"name\":\"\\u4f18\\u60e0\\u52381\",\"type\":\"1\",\"main\":\"2\",\"common_rate\":\"1000\"}', 1, '2019-02-26 06:32:11', '2019-02-26 06:32:11', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (48, '/api/admin/coupon/retrieve/6', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ApiController.php', 33, 'Argument 1 passed to App\\Http\\Controllers\\Admin\\ApiController::_success() must be of the type array, object given, called in /Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php on line 102', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieve\\/6\"}', 1, '2019-02-26 06:56:41', '2019-02-26 06:56:41', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (49, '/api/admin/coupon/retrieve/6', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 104, '', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieve\\/6\"}', 1, '2019-02-26 06:57:19', '2019-02-26 06:57:19', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (50, '/api/admin/coupon/retrieve/6', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 104, '优惠券方案不存在', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieve\\/6\"}', 1, '2019-02-26 06:58:37', '2019-02-26 06:58:37', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (51, '/api/admin/coupon/retrieveall', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\"}', 1, '2019-02-26 07:16:34', '2019-02-26 07:16:34', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (52, '/swagger/index', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/zircote/swagger-php/src/Logger.php', 38, '$ref \"#/definitions/ReceiveAddress\" not found for @SWG\\Items() in \\App\\Http\\Controllers\\Admin\\CouponController->retrieve() in /Users/yangkunlin/git/chemi_api_insurance.com/app/Http/../Http/Controllers/Admin/CouponController.php on line 72', '{\"s\":\"\\/swagger\\/index\"}', 1, '2019-02-26 08:50:48', '2019-02-26 08:50:48', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (53, '/swagger/index', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/zircote/swagger-php/src/Logger.php', 38, 'definition is already defined for object \"Swagger\\Annotations\\Definition\" in /Users/yangkunlin/git/chemi_api_insurance.com/app/Http/../Http/Model/BaseModel.php on line 21', '{\"s\":\"\\/swagger\\/index\"}', 1, '2019-02-26 10:10:10', '2019-02-26 10:10:10', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (54, '/coupon/retrieveall', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"s\":\"\\/coupon\\/retrieveall\"}', 1, '2019-02-26 10:21:11', '2019-02-26 10:21:11', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (55, '/admin/coupon/retrieveall', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"s\":\"\\/admin\\/coupon\\/retrieveall\"}', 1, '2019-02-26 10:22:21', '2019-02-26 10:22:21', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (56, '/api/admin/coupon/retrieve', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieve\"}', 1, '2019-02-26 10:29:19', '2019-02-26 10:29:19', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (57, '/api/admin/coupon/retrieve', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieve\"}', 1, '2019-02-26 10:30:37', '2019-02-26 10:30:37', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (58, '/api/admin/coupon/update/7', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"name\":\"\\u65b9\\u68481\",\"type\":\"1\",\"main\":\"3\",\"common_rate\":\"100\",\"maintain_rate\":\"3\"}', 1, '2019-02-27 02:01:30', '2019-02-27 02:01:30', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (59, '/api/admin/coupon/update/7', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 259, '优惠券方案不存在', '{\"name\":\"\\u65b9\\u68481\",\"type\":\"1\",\"main\":\"3\",\"common_rate\":\"100\",\"maintain_rate\":\"3\"}', 1, '2019-02-27 02:02:16', '2019-02-27 02:02:16', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (60, '/api/admin/coupon/delete/9', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 308, '删除优惠券方案失败', '[]', 1, '2019-02-27 02:11:13', '2019-02-27 02:11:13', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (61, '/api/admin/coupon/delete/2', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 308, '删除优惠券方案失败', '[]', 1, '2019-02-27 02:11:47', '2019-02-27 02:11:47', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (62, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 98, '优惠方案名称不可重名', '{\"name\":\"\\u4f18\\u60e0\\u52381\",\"type\":\"2\",\"main\":\"2\",\"common_rate\":\"100\"}', 1, '2019-02-27 03:43:21', '2019-02-27 03:43:21', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (63, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 98, '优惠方案名称不可重名', '{\"name\":\"\\u4f18\\u60e0\\u52382\",\"type\":\"2\",\"main\":\"2\",\"common_rate\":\"100\"}', 1, '2019-02-27 03:43:48', '2019-02-27 03:43:48', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (64, '/api/admin/company/create', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"name\":\"\\u4fdd\\u96691\",\"app_coupon\":\"2\",\"agent_coupon\":\"3\",\"order_type\":\"1\"}', 1, '2019-02-27 06:12:22', '2019-02-27 06:12:22', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (65, '/api/admin/company/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Rules/CompanyRule.php', 25, 'Class \'App\\Model\\Company\' not found', '{\"name\":\"\\u4fdd\\u96691\",\"app_coupon\":\"2\",\"agent_coupon\":\"3\",\"order_type\":\"1\"}', 1, '2019-02-27 06:13:52', '2019-02-27 06:13:52', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (66, '/api/admin/company/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Rules/CompanyRule.php', 25, 'Class \'App\\Model\\Company\' not found', '{\"name\":\"\\u4fdd\\u96691\",\"app_coupon\":\"2\",\"agent_coupon\":\"3\",\"order_type\":\"1\"}', 1, '2019-02-27 06:19:07', '2019-02-27 06:19:07', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (67, '/api/admin/company/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Rules/CompanyRule.php', 25, 'Class \'App\\Model\\Company\' not found', '{\"name\":\"\\u4fdd\\u96691\",\"app_coupon\":\"2\",\"agent_coupon\":\"3\",\"order_type\":\"1\"}', 1, '2019-02-27 06:23:07', '2019-02-27 06:23:07', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (68, '/api/admin/company/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Rules/CompanyRule.php', 25, 'Class \'App\\Model\\Company\' not found', '{\"name\":\"\\u4fdd\\u96691\",\"app_coupon\":\"2\",\"agent_coupon\":\"3\",\"order_type\":\"1\"}', 1, '2019-02-27 06:25:06', '2019-02-27 06:25:06', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (69, '/api/admin/company/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CompanyController.php', 86, '保险公司不可重名', '{\"name\":\"\\u4fdd\\u96691\",\"app_coupon\":\"2\",\"agent_coupon\":\"3\",\"order_type\":\"1\"}', 1, '2019-02-27 14:32:57', '2019-02-27 14:32:57', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (70, '/api/admin/company/retrieveall?name=&type=2', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'type\' in \'where clause\' (SQL: select count(*) as aggregate from `baoxian_company` where (`type` = 2))', '{\"s\":\"\\/api\\/admin\\/company\\/retrieveall\",\"name\":null,\"type\":\"2\"}', 1, '2019-02-27 14:34:57', '2019-02-27 14:34:57', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (71, '/api/admin/coupon/update/4098', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 262, '请输入方案类型', '{\"name\":\"\\u4fdd\\u96692\",\"app_coupon\":\"1\",\"agent_coupon\":\"4\",\"order_type\":\"1\",\"status\":\"0\"}', 1, '2019-02-27 14:41:33', '2019-02-27 14:41:33', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (72, '/api/admin/company/update/4098', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CompanyController.php', 237, '保险公司不可重名', '{\"name\":\"\\u4fdd\\u96692\",\"app_coupon\":\"1\",\"agent_coupon\":\"4\",\"order_type\":\"1\",\"status\":\"0\"}', 1, '2019-02-27 14:41:59', '2019-02-27 14:41:59', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (73, '/api/admin/company/update/4098', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Rules/CompanyRule.php', 25, 'Undefined property: App\\Rules\\CompanyRule::$id', '{\"name\":\"\\u4fdd\\u96692\",\"app_coupon\":\"1\",\"agent_coupon\":\"4\",\"order_type\":\"1\",\"status\":\"0\"}', 1, '2019-02-27 14:53:14', '2019-02-27 14:53:14', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (74, '/api/admin/company/update/4098', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CompanyController.php', 237, '保险公司不可重名', '{\"name\":\"\\u4fdd\\u96692\",\"app_coupon\":\"1\",\"agent_coupon\":\"4\",\"order_type\":\"1\",\"status\":\"0\"}', 1, '2019-02-27 14:54:12', '2019-02-27 14:54:12', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (75, '/api/admin/company/update/4098', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CompanyController.php', 237, '保险公司不可重名', '{\"name\":\"\\u4fdd\\u96692\",\"app_coupon\":\"1\",\"agent_coupon\":\"4\",\"order_type\":\"1\",\"status\":\"0\"}', 1, '2019-02-27 14:54:35', '2019-02-27 14:54:35', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (76, '/api/admin/coupon/delete/4098', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 308, '删除优惠券方案失败', '[]', 1, '2019-02-27 14:59:38', '2019-02-27 14:59:38', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (77, '/api/admin/company/delete/4098', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CompanyController.php', 283, '删除优惠券方案失败', '[]', 1, '2019-02-27 15:00:11', '2019-02-27 15:00:11', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (78, '/api/admin/company/delete/4098', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CompanyController.php', 283, '删除保险公司信息失败', '[]', 1, '2019-02-27 15:01:46', '2019-02-27 15:01:46', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (79, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Validator/CouponValidator.php', 15, 'Too few arguments to function App\\Http\\Validator\\CouponValidator::__construct(), 0 passed in /Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php on line 96 and exactly 1 expected', '{\"name\":\"\\u4f18\\u60e0\\u52382\",\"type\":\"2\",\"main\":\"2\",\"common_rate\":\"100\"}', 1, '2019-02-27 15:02:12', '2019-02-27 15:02:12', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (80, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 98, '优惠方案名称不可重名', '{\"name\":\"\\u4f18\\u60e0\\u52382\",\"type\":\"2\",\"main\":\"2\",\"common_rate\":\"100\"}', 1, '2019-02-27 15:06:33', '2019-02-27 15:06:33', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (81, '/api/admin/coupon/update/10', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 262, '优惠方案名称不可重名', '{\"name\":\"\\u65b9\\u68481\",\"type\":\"1\",\"main\":\"3\",\"common_rate\":\"100\",\"maintain_rate\":\"3\"}', 1, '2019-02-27 15:07:17', '2019-02-27 15:07:17', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (82, '/api/admin/order/retrieveall?mobile=1111', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/OrderController.php', 93, 'Class \'App\\Model\\OrderPage\' not found', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"mobile\":\"1111\"}', 1, '2019-02-27 18:40:26', '2019-02-27 18:40:26', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (83, '/api/admin/order/retrieveall?mobile=1111', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/OrderController.php', 93, 'Class \'App\\Model\\OrderPage\' not found', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"mobile\":\"1111\"}', 1, '2019-02-27 18:41:13', '2019-02-27 18:41:13', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (84, '/api/admin/config/recommond/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"s\":\"\\/api\\/admin\\/config\\/recommond\\/base\"}', 1, '2019-02-28 14:22:46', '2019-02-28 14:22:46', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (85, '/api/admin/config/recommend/base', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 68, 'Class \'App\\Model\\Config\' not found', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/base\"}', 1, '2019-02-28 14:40:42', '2019-02-28 14:40:42', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (86, '/api/admin/config/full/recommend/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"s\":\"\\/api\\/admin\\/config\\/full\\/recommend\\/base\"}', 1, '2019-02-28 15:14:35', '2019-02-28 15:14:35', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (87, '/api/admin/config/full/recommend/hot', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"s\":\"\\/api\\/admin\\/config\\/full\\/recommend\\/hot\"}', 1, '2019-02-28 15:14:35', '2019-02-28 15:14:35', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (88, '/api/admin/config/full/recommend/best', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"s\":\"\\/api\\/admin\\/config\\/full\\/recommend\\/best\"}', 1, '2019-02-28 15:14:36', '2019-02-28 15:14:36', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (89, '/api/admin/config/recommend/update/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/Controller.php', 68, 'Method App\\Http\\Controllers\\Admin\\CompanyController::updateRecommend does not exist.', '[]', 1, '2019-02-28 16:00:51', '2019-02-28 16:00:51', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (90, '/api/admin/config/recommend/update/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'0\' in \'where clause\' (SQL: delete from `baoxian_config` where (`0` = name and `1` = RECOMMEND_BASE_FORCEINSURANCE))', '{\"FORCEINSURANCE\":\"0\",\"TAXPAY\":\"1\",\"CHESUN\":\"1\",\"BUJIMIAN_CHESUN\":\"1\",\"SANZHE\":\"0\",\"BUJIMIAN_SANZHE\":\"0\",\"DAOQIANG\":\"1\",\"BUJIMIAN_DAOQIANG\":\"1\",\"SIJI\":\"1\",\"BUJIMIAN_SIJI\":\"1\",\"CHENGKE\":\"0\",\"BUJIMIAN_CHENGKE\":\"0\",\"LOSTSPECIAL\":\"1\",\"BUJIMIAN_LOSTSPECIAL\":\"1\",\"SHESHUI\":null,\"BUJIMIAN_SHESHUI\":null,\"BOLI\":null,\"HUAHEN\":null,\"BUJIMIAN_HUAHEN\":null,\"ZIRAN\":null,\"BUJIMIAN_ZIRAN\":null,\"TAKESPECAILREPAIR\":null,\"SANZHEHOLIDAYDOUBLE\":null}', 1, '2019-02-28 16:02:23', '2019-02-28 16:02:23', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (91, '/api/admin/config/recommend/update/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'create_time\' in \'field list\' (SQL: insert into `baoxian_config` (`name`, `type`, `value`, `create_time`) values (RECOMMEND_BASE_TAXPAY, number, 1, 2019-02-28 16:03:41))', '{\"FORCEINSURANCE\":\"0\",\"TAXPAY\":\"1\",\"CHESUN\":\"1\",\"BUJIMIAN_CHESUN\":\"1\",\"SANZHE\":\"0\",\"BUJIMIAN_SANZHE\":\"0\",\"DAOQIANG\":\"1\",\"BUJIMIAN_DAOQIANG\":\"1\",\"SIJI\":\"1\",\"BUJIMIAN_SIJI\":\"1\",\"CHENGKE\":\"0\",\"BUJIMIAN_CHENGKE\":\"0\",\"LOSTSPECIAL\":\"1\",\"BUJIMIAN_LOSTSPECIAL\":\"1\",\"SHESHUI\":null,\"BUJIMIAN_SHESHUI\":null,\"BOLI\":null,\"HUAHEN\":null,\"BUJIMIAN_HUAHEN\":null,\"ZIRAN\":null,\"BUJIMIAN_ZIRAN\":null,\"TAKESPECAILREPAIR\":null,\"SANZHEHOLIDAYDOUBLE\":null}', 1, '2019-02-28 16:03:41', '2019-02-28 16:03:41', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (92, '/api/admin/config/recommend/update/hot', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 296, 'Class \'App\\Http\\Controllers\\Admin\\ConfigValidator\' not found', '{\"FORCEINSURANCE\":\"0\",\"TAXPAY\":\"1\",\"CHESUN\":\"1\",\"BUJIMIAN_CHESUN\":\"1\",\"SANZHE\":\"0\",\"BUJIMIAN_SANZHE\":\"0\",\"DAOQIANG\":\"1\",\"BUJIMIAN_DAOQIANG\":\"1\",\"SIJI\":null,\"BUJIMIAN_SIJI\":null,\"CHENGKE\":\"0\",\"BUJIMIAN_CHENGKE\":\"0\",\"LOSTSPECIAL\":\"s\",\"BUJIMIAN_LOSTSPECIAL\":\"1\",\"SHESHUI\":null,\"BUJIMIAN_SHESHUI\":null,\"BOLI\":null,\"HUAHEN\":null,\"BUJIMIAN_HUAHEN\":null,\"ZIRAN\":null,\"BUJIMIAN_ZIRAN\":null,\"TAKESPECAILREPAIR\":null,\"SANZHEHOLIDAYDOUBLE\":null}', 1, '2019-02-28 16:45:00', '2019-02-28 16:45:00', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (93, '/api/admin/config/recommend/update/hot', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 299, '输入类型必须为整数', '{\"FORCEINSURANCE\":\"0\",\"TAXPAY\":\"1\",\"CHESUN\":\"1\",\"BUJIMIAN_CHESUN\":\"1\",\"SANZHE\":\"0\",\"BUJIMIAN_SANZHE\":\"0\",\"DAOQIANG\":\"1\",\"BUJIMIAN_DAOQIANG\":\"1\",\"SIJI\":null,\"BUJIMIAN_SIJI\":null,\"CHENGKE\":\"0\",\"BUJIMIAN_CHENGKE\":\"0\",\"LOSTSPECIAL\":\"s\",\"BUJIMIAN_LOSTSPECIAL\":\"1\",\"SHESHUI\":null,\"BUJIMIAN_SHESHUI\":null,\"BOLI\":null,\"HUAHEN\":null,\"BUJIMIAN_HUAHEN\":null,\"ZIRAN\":null,\"BUJIMIAN_ZIRAN\":null,\"TAKESPECAILREPAIR\":null,\"SANZHEHOLIDAYDOUBLE\":null}', 1, '2019-02-28 17:00:40', '2019-02-28 17:00:40', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (94, '/api/admin/config/recommend/update/hot', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 299, '输入类型必须为整数', '{\"FORCEINSURANCE\":\"0\",\"TAXPAY\":\"1\",\"CHESUN\":\"1\",\"BUJIMIAN_CHESUN\":\"1\",\"SANZHE\":\"0\",\"BUJIMIAN_SANZHE\":\"0\",\"DAOQIANG\":\"1\",\"BUJIMIAN_DAOQIANG\":\"1\",\"SIJI\":null,\"BUJIMIAN_SIJI\":null,\"CHENGKE\":\"0\",\"BUJIMIAN_CHENGKE\":\"0\",\"LOSTSPECIAL\":\"0\",\"BUJIMIAN_LOSTSPECIAL\":\"1\",\"SHESHUI\":null,\"BUJIMIAN_SHESHUI\":null,\"BOLI\":null,\"HUAHEN\":null,\"BUJIMIAN_HUAHEN\":null,\"ZIRAN\":null,\"BUJIMIAN_ZIRAN\":null,\"TAKESPECAILREPAIR\":null,\"SANZHEHOLIDAYDOUBLE\":null}', 1, '2019-02-28 17:01:00', '2019-02-28 17:01:00', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (95, '/api/admin/config/recommend/update/hot', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 299, '输入类型必须为整数', '{\"FORCEINSURANCE\":\"0\",\"TAXPAY\":\"1\",\"CHESUN\":\"1\",\"BUJIMIAN_CHESUN\":\"1\",\"SANZHE\":\"0\",\"BUJIMIAN_SANZHE\":\"0\",\"DAOQIANG\":\"1\",\"BUJIMIAN_DAOQIANG\":\"1\",\"SIJI\":\"0\",\"BUJIMIAN_SIJI\":\"0\",\"CHENGKE\":\"0\",\"BUJIMIAN_CHENGKE\":\"0\",\"LOSTSPECIAL\":\"0\",\"BUJIMIAN_LOSTSPECIAL\":\"1\",\"SHESHUI\":null,\"BUJIMIAN_SHESHUI\":\"0\",\"BOLI\":\"0\",\"HUAHEN\":\"0\",\"BUJIMIAN_HUAHEN\":\"0\",\"ZIRAN\":\"0\",\"BUJIMIAN_ZIRAN\":\"0\",\"TAKESPECAILREPAIR\":\"0\",\"SANZHEHOLIDAYDOUBLE\":\"0\"}', 1, '2019-02-28 17:01:38', '2019-02-28 17:01:38', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (96, '/api/admin/config/recommend/update/hots', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 299, '推荐方案类型错误', '{\"FORCEINSURANCE\":\"0\",\"TAXPAY\":\"1\",\"CHESUN\":\"1\",\"BUJIMIAN_CHESUN\":\"1\",\"SANZHE\":\"0\",\"BUJIMIAN_SANZHE\":\"0\",\"DAOQIANG\":\"1\",\"BUJIMIAN_DAOQIANG\":\"1\",\"SIJI\":\"0\",\"BUJIMIAN_SIJI\":\"0\",\"CHENGKE\":\"0\",\"BUJIMIAN_CHENGKE\":\"0\",\"LOSTSPECIAL\":\"0\",\"BUJIMIAN_LOSTSPECIAL\":\"1\",\"SHESHUI\":\"0\",\"BUJIMIAN_SHESHUI\":\"0\",\"BOLI\":\"0\",\"HUAHEN\":\"0\",\"BUJIMIAN_HUAHEN\":\"0\",\"ZIRAN\":\"0\",\"BUJIMIAN_ZIRAN\":\"0\",\"TAKESPECAILREPAIR\":\"0\",\"SANZHEHOLIDAYDOUBLE\":\"0\"}', 1, '2019-02-28 17:02:28', '2019-02-28 17:02:28', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (97, '/api/admin/config/recommend/bases', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 63, '推荐方案类型错误', '[]', 1, '2019-02-28 17:04:38', '2019-02-28 17:04:38', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (98, '/api/admin/config/recommend/full/bases', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 119, '推荐方案类型错误', '[]', 1, '2019-02-28 17:05:02', '2019-02-28 17:05:02', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (99, '/swagger/index', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/zircote/swagger-php/src/Logger.php', 38, '[Syntax Error] Expected Doctrine\\Common\\Annotations\\DocLexer::T_CLOSE_PARENTHESIS, got \'description\' in \\App\\Http\\Controllers\\Admin\\ConfigController->fullRecommend() in /Users/yangkunlin/git/chemi_api_insurance.com/app/Http/../Http/Controllers/Admin/ConfigController.php on line 109:25', '{\"s\":\"\\/swagger\\/index\"}', 1, '2019-02-28 17:40:09', '2019-02-28 17:40:09', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (100, '/swagger/index', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/zircote/swagger-php/src/Logger.php', 38, '[Syntax Error] Expected Doctrine\\Common\\Annotations\\DocLexer::T_CLOSE_PARENTHESIS, got \'description\' in \\App\\Http\\Controllers\\Admin\\ConfigController->fullRecommend() in /Users/yangkunlin/git/chemi_api_insurance.com/app/Http/../Http/Controllers/Admin/ConfigController.php on line 109:25', '{\"s\":\"\\/swagger\\/index\"}', 1, '2019-02-28 17:40:15', '2019-02-28 17:40:15', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (101, '/swagger/$/definitions/Config', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"s\":\"\\/swagger\\/$\\/definitions\\/Config\"}', 1, '2019-02-28 17:41:17', '2019-02-28 17:41:17', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (102, '/api/admin/company/delete/4098', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CompanyController.php', 290, '删除保险公司信息失败', '[]', 1, '2019-02-28 17:46:55', '2019-02-28 17:46:55', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (103, '/api/admin/config/recommend/full/host', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 122, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/host\"}', 1, '2019-02-28 18:01:31', '2019-02-28 18:01:31', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (104, '/api/admin/config/recommend/best', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Redis/Connectors/PredisConnector.php', 25, 'Class \'Predis\\Client\' not found', '[]', 1, '2019-02-28 18:36:42', '2019-02-28 18:36:42', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (105, '/api/admin/config/recommend/best', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Connection refused [tcp://127.0.0.1:6379]', '[]', 1, '2019-02-28 18:41:47', '2019-02-28 18:41:47', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (106, '/api/admin/config/recommend/update/base', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 332, '输入类型必须为整数', '{\"FORCEINSURANCE\":\"0\",\"TAXPAY\":\"1\",\"CHESUN\":\"1\",\"BUJIMIAN_CHESUN\":\"1\",\"SANZHE\":\"0\",\"BUJIMIAN_SANZHE\":\"0\",\"DAOQIANG\":\"1\",\"BUJIMIAN_DAOQIANG\":\"1\",\"SIJI\":null,\"BUJIMIAN_SIJI\":null,\"CHENGKE\":\"0\",\"BUJIMIAN_CHENGKE\":\"0\",\"LOSTSPECIAL\":\"1\",\"BUJIMIAN_LOSTSPECIAL\":\"1\",\"SHESHUI\":null,\"BUJIMIAN_SHESHUI\":null,\"BOLI\":null,\"HUAHEN\":null,\"BUJIMIAN_HUAHEN\":null,\"ZIRAN\":null,\"BUJIMIAN_ZIRAN\":null,\"TAKESPECAILREPAIR\":null,\"SANZHEHOLIDAYDOUBLE\":null}', 1, '2019-02-28 18:44:57', '2019-02-28 18:44:57', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (107, '/api/admin/config/recommend/update/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Profile/RedisProfile.php', 88, 'Command \'DELETE\' is not a registered Redis command.', '{\"FORCEINSURANCE\":\"0\",\"TAXPAY\":\"1\",\"CHESUN\":\"1\",\"BUJIMIAN_CHESUN\":\"1\",\"SANZHE\":\"0\",\"BUJIMIAN_SANZHE\":\"0\",\"DAOQIANG\":\"1\",\"BUJIMIAN_DAOQIANG\":\"1\",\"SIJI\":\"0\",\"BUJIMIAN_SIJI\":\"0\",\"CHENGKE\":\"0\",\"BUJIMIAN_CHENGKE\":\"0\",\"LOSTSPECIAL\":\"1\",\"BUJIMIAN_LOSTSPECIAL\":\"1\",\"SHESHUI\":\"0\",\"BUJIMIAN_SHESHUI\":\"0\",\"BOLI\":\"0\",\"HUAHEN\":\"0\",\"BUJIMIAN_HUAHEN\":\"0\",\"ZIRAN\":\"0\",\"BUJIMIAN_ZIRAN\":\"0\",\"TAKESPECAILREPAIR\":\"0\",\"SANZHEHOLIDAYDOUBLE\":\"0\"}', 1, '2019-02-28 18:45:24', '2019-02-28 18:45:24', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (108, '/api/admin/order/retrieveall?mobile=111', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[42S02]: Base table or view not found: 1146 Table \'chemi_api_v1.baoxian_order_page_201902\' doesn\'t exist (SQL: select count(*) as aggregate from `baoxian_order_page_201902` where (`insuredmobile` like %111% or `holdermobile` like %111%))', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"mobile\":\"111\"}', 1, '2019-03-01 15:11:51', '2019-03-01 15:11:51', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (109, '/api/admin/coupon/retrieve/2', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 140, '优惠券方案不存在', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieve\\/2\"}', 1, '2019-03-01 17:19:41', '2019-03-01 17:19:41', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (110, '/api/admin/coupon/delete/8', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:53:07', '2019-03-01 17:53:07', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (111, '/api/admin/coupon/delete/8', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:53:08', '2019-03-01 17:53:08', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (112, '/api/admin/coupon/delete/8', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:53:09', '2019-03-01 17:53:09', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (113, '/api/admin/coupon/delete/8', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:53:10', '2019-03-01 17:53:10', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (114, '/api/admin/coupon/delete/7', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:53:10', '2019-03-01 17:53:10', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (115, '/api/admin/coupon/delete/7', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:53:11', '2019-03-01 17:53:11', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (116, '/api/admin/coupon/delete/7', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:53:11', '2019-03-01 17:53:11', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (117, '/api/admin/coupon/delete/7', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:53:11', '2019-03-01 17:53:11', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (118, '/api/admin/coupon/delete/8', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:54:05', '2019-03-01 17:54:05', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (119, '/api/admin/coupon/delete/6', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:56:45', '2019-03-01 17:56:45', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (120, '/api/admin/coupon/delete/6', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:56:59', '2019-03-01 17:56:59', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (121, '/api/admin/coupon/delete/4', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 17:59:26', '2019-03-01 17:59:26', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (122, '/api/admin/coupon/delete/3', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 18:00:24', '2019-03-01 18:00:24', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (123, '/api/admin/coupon/delete/5', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 18:04:57', '2019-03-01 18:04:57', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (124, '/api/admin/coupon/delete/5', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 18:05:00', '2019-03-01 18:05:00', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (125, '/api/admin/coupon/delete/5', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 18:05:00', '2019-03-01 18:05:00', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (126, '/api/admin/coupon/delete/5', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 18:05:01', '2019-03-01 18:05:01', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (127, '/api/admin/coupon/delete/5', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 18:05:01', '2019-03-01 18:05:01', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (128, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 98, '方案名称为空', '[]', 1, '2019-03-01 18:11:06', '2019-03-01 18:11:06', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (129, '/api/admin/coupon/delete/11', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 320, '删除优惠券方案失败', '[]', 1, '2019-03-01 18:12:05', '2019-03-01 18:12:05', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (130, '/api/admin/coupon/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[HY000] [2002] Operation timed out (SQL: select count(*) as aggregate from `baoxian_coupon_plan`)', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-01 17:10:15', '2019-03-01 17:10:15', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (131, '/api/admin/coupon/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: select count(*) as aggregate from `baoxian_coupon_plan`), {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}, 1, GET, 1, 2019-03-04 09:24:24, 2019-03-04 09:24:24, {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"})), 1, GET, 1, 2019-03-04 09:24:54, 2019-03-04 09:24:54, {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}, 1)), GET, 1, 2019-03-04 09:25:24, 2019-03-04 09:25:24, {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}, 1, GET)), 1, 2019-03-04 09:25:54, 2019-03-04 09:25:54, {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}, 1, GET, 1)), 2019-03-04 09:26:24, 2019-03-04 09:26:24, {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}, 1, GET, 1, 2019-03-04 09:26:54)), 2019-03-04 09:26:54, {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}, 1, GET, 1, 2019-03-04 09:27:24, 2019-03-04 09:27:24)), {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}, 1, GET, 1, 2019-03-04 09:27:54, 2019-03-04 09:27:54, ?)), ?, ?, ?, ?, ?, ?, ?))', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-04 09:28:24', '2019-03-04 09:28:24', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (132, '/api/admin/coupon/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: select count(*) as aggregate from `baoxian_coupon_plan`), {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}, 1, GET, 1, 2019-03-04 09:28:11, 2019-03-04 09:28:11, {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"})), 1, GET, 1, 2019-03-04 09:28:41, 2019-03-04 09:28:41, ?, ?))', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-04 09:29:11', '2019-03-04 09:29:11', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (133, '/api/admin/coupon/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: select count(*) as aggregate from `baoxian_coupon_plan`), {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}, 1, GET, 1, 2019-03-04 09:30:55, 2019-03-04 09:30:55, ?))', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-04 09:31:25', '2019-03-04 09:31:25', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (134, '/api/getInsuranceClass', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Container/Container.php', 779, 'Class Barryvdh\\Cors\\HandleCors does not exist', '[]', 1, '2019-03-04 11:03:42', '2019-03-04 11:03:42', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (135, '/api/getInsuranceClass', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Container/Container.php', 779, 'Class Barryvdh\\Cors\\HandleCors does not exist', '[]', 1, '2019-03-04 11:03:43', '2019-03-04 11:03:43', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (136, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 98, '洗车保养劵返还比例必须为0-100的整数', '{\"id\":0,\"name\":\"\\u65b9\\u68481\",\"type\":\"1\",\"main\":\"1\",\"common_rate\":\"1\",\"park_rate\":\"3\",\"maintain_rate\":null,\"insurance_rate\":null}', 1, '2019-03-04 11:37:28', '2019-03-04 11:37:28', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (137, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 98, '优惠方案名称不可重名', '{\"name\":\"\\u65b9\\u68481\",\"type\":\"1\",\"main\":\"2\",\"common_rate\":\"2\",\"park_rate\":\"4\",\"maintain_rate\":\"5\"}', 1, '2019-03-04 13:51:22', '2019-03-04 13:51:22', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (138, '/api/admin/coupon/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CouponController.php', 98, '优惠方案名称不可重名', '{\"name\":\"\\u65b9\\u684823\",\"type\":\"2\",\"main\":\"2\",\"common_rate\":\"2\",\"park_rate\":\"3\",\"maintain_rate\":\"4\"}', 1, '2019-03-04 13:51:45', '2019-03-04 13:51:45', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (139, '/api/getInsuranceClass', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-04 14:58:52', '2019-03-04 14:58:52', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (140, '/api/getInsuranceClass', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-04 14:59:15', '2019-03-04 14:59:15', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (141, '/api/getInsuranceClass', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-04 14:59:19', '2019-03-04 14:59:19', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (142, '/api/getPrecisePrice', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[42S02]: Base table or view not found: 1146 Table \'chemi_api_v1.baoxian_baoxian_company\' doesn\'t exist (SQL: select `id`, `name`, `tel`, `logo` from `baoxian_baoxian_company` where `status` = 1)', '[]', 1, '2019-03-04 15:58:16', '2019-03-04 15:58:16', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (143, '/api/getPrecisePrice', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[42S02]: Base table or view not found: 1146 Table \'chemi_api_v1.baoxian_baoxian_company\' doesn\'t exist (SQL: select `id`, `name`, `tel`, `logo` from `baoxian_baoxian_company` where `status` = 1)', '[]', 1, '2019-03-04 15:58:56', '2019-03-04 15:58:56', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (144, '/api/admin/order/retrieveall?mobile=111', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[42S02]: Base table or view not found: 1146 Table \'chemi_api_v1.baoxian_order_page_201902\' doesn\'t exist (SQL: select count(*) as aggregate from `baoxian_order_page_201902` where (`insuredmobile` like %111% or `holdermobile` like %111%))', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"mobile\":\"111\"}', 1, '2019-03-04 16:57:46', '2019-03-04 16:57:46', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (145, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 10:10:37', '2019-03-05 10:10:37', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (146, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 10:10:37', '2019-03-05 10:10:37', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (147, '/api/admin/order/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'company\' in \'field list\' (SQL: select `id`, `licenseno`, `carownersname`, `holdermobile`, `businessenddate`, `forceenddate`, `businessenddate`, `biztotal`, `forcetotal`, `taxtotal`, `company`, `target`, `agent_id`, `status` from `baoxian_order_page_201903` order by `id` desc limit 20 offset 0)', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-05 10:54:24', '2019-03-05 10:54:24', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (148, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:00:29', '2019-03-05 11:00:29', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (149, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:00:31', '2019-03-05 11:00:31', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (150, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:00:33', '2019-03-05 11:00:33', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (151, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:00:34', '2019-03-05 11:00:34', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (152, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:01:30', '2019-03-05 11:01:30', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (153, '/api/admin/order/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Support/Traits/ForwardsCalls.php', 50, 'Call to undefined method Illuminate\\Database\\Eloquent\\Relations\\HasOne::toArray()', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-05 11:01:55', '2019-03-05 11:01:55', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (154, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:01:42', '2019-03-05 11:01:42', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (155, '/api/admin/order/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Support/Traits/ForwardsCalls.php', 50, 'Call to undefined method Illuminate\\Database\\Eloquent\\Relations\\HasOne::toArray()', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-05 11:03:20', '2019-03-05 11:03:20', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (156, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:04:09', '2019-03-05 11:04:09', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (157, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:04:19', '2019-03-05 11:04:19', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (158, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:05:42', '2019-03-05 11:05:42', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (159, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:06:35', '2019-03-05 11:06:35', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (160, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:07:27', '2019-03-05 11:07:27', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (161, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:08:05', '2019-03-05 11:08:05', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (162, '/api/admin/order/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/DatabaseManager.php', 327, 'Call to undefined method Illuminate\\Database\\MySqlConnection::getLastSql()', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-05 11:08:59', '2019-03-05 11:08:59', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (163, '/api/getCompanyList', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:08:59', '2019-03-05 11:08:59', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (164, '/api/admin/order/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Support/Traits/Macroable.php', 100, 'Method Illuminate\\Database\\Eloquent\\Collection::toSql does not exist.', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-05 11:10:05', '2019-03-05 11:10:05', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (165, '/api/admin/order/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'baoxian_company.source\' in \'where clause\' (SQL: select * from `baoxian_company` where `baoxian_company`.`source` = 1 and `baoxian_company`.`source` is not null limit 1)', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-05 11:13:07', '2019-03-05 11:13:07', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (166, '/api/admin/order/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Support/Traits/ForwardsCalls.php', 50, 'Call to undefined method Illuminate\\Database\\Eloquent\\Relations\\HasOne::getResult()', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-05 11:16:42', '2019-03-05 11:16:42', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (167, '/api/config', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-05 11:49:42', '2019-03-05 11:49:42', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (168, '/api/getCouponPlanRate', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-05 11:50:12', '2019-03-05 11:50:12', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (169, '/api/getCouponPlanRate', '/home/vagrant/Code/cmbx/app/Http/Model/Baoxian.php', 750, 'Cannot use a scalar value as an array', '{\"source\":\"4097\"}', 1, '2019-03-05 11:57:19', '2019-03-05 11:57:19', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (170, '/api/getCouponPlanRate', '/home/vagrant/Code/cmbx/app/Http/Model/Baoxian.php', 750, 'Cannot use a scalar value as an array', '{\"source\":\"4097\"}', 1, '2019-03-05 11:57:37', '2019-03-05 11:57:37', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (171, '/api/getCouponPlanRate', '/home/vagrant/Code/cmbx/app/Http/Model/Baoxian.php', 750, 'Cannot use a scalar value as an array', '{\"source\":\"4097\"}', 1, '2019-03-05 11:57:58', '2019-03-05 11:57:58', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (172, '/api/admin/order/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/order/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phppage=1&limit=20, 664, SQLSTATE[HY000] [2002] Operation timed out (SQL: select count(*) as aggregate from `baoxian_order_page_201903`), {\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}, 1, GET, 1, 2019-03-05 13:52:13, 2019-03-05 13:52:13, ?))', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-05 13:52:43', '2019-03-05 13:52:43', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (173, '/api/admin/order/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[HY000] [2002] Operation timed out (SQL: select count(*) as aggregate from `baoxian_order_page_201903`)', '{\"s\":\"\\/api\\/admin\\/order\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-05 13:52:40', '2019-03-05 13:52:40', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (174, '/api/getPrepareCoupon', '/home/vagrant/Code/cmbx/app/helpers.php', 167, 'A non-numeric value encountered', '{\"source\":\"4097\"}', 1, '2019-03-05 15:14:33', '2019-03-05 15:14:33', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (175, '/api/admin/order/retrieve/1', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/OrderController.php', 64, '订单信息不存在', '{\"s\":\"\\/api\\/admin\\/order\\/retrieve\\/1\"}', 1, '2019-03-05 15:19:05', '2019-03-05 15:19:05', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (176, '/api/admin/order/retrieve/1', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/OrderController.php', 64, '订单信息不存在', '{\"s\":\"\\/api\\/admin\\/order\\/retrieve\\/1\"}', 1, '2019-03-05 15:19:09', '2019-03-05 15:19:09', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (177, '/api/admin/company/update/4097', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CompanyController.php', 241, '状态格式不正确', '{\"name\":\"\\u4fdd\\u96691\",\"status\":false,\"app_coupon\":23,\"agent_coupon\":22,\"order_type\":1}', 1, '2019-03-05 18:09:29', '2019-03-05 18:09:29', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (178, '/api/admin/company/create', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/CompanyController.php', 86, '保险公司不可重名', '{\"name\":\"\\u4fdd\\u96691\",\"status\":1,\"app_coupon\":23,\"order_type\":1}', 1, '2019-03-05 18:18:33', '2019-03-05 18:18:33', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (179, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:05', '2019-03-06 17:18:05', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (180, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:06', '2019-03-06 17:18:06', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (181, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:07', '2019-03-06 17:18:07', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (182, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:07', '2019-03-06 17:18:07', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (183, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:07', '2019-03-06 17:18:07', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (184, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:07', '2019-03-06 17:18:07', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (185, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:08', '2019-03-06 17:18:08', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (186, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:08', '2019-03-06 17:18:08', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (187, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:08', '2019-03-06 17:18:08', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (188, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:08', '2019-03-06 17:18:08', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (189, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:08', '2019-03-06 17:18:08', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (190, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:09', '2019-03-06 17:18:09', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (191, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:13', '2019-03-06 17:18:13', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (192, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:13', '2019-03-06 17:18:13', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (193, '/api/admin/config/recommend/full/undefined', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 135, '推荐方案类型错误', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/undefined\"}', 1, '2019-03-06 17:18:18', '2019-03-06 17:18:18', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (194, '/api/admin/config/recommend/update/base', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 332, '输入类型必须为整数', '{\"FORCEINSURANCE\":null,\"TAXPAY\":null,\"CHESUN\":\"1\",\"BUJIMIAN_CHESUN\":null,\"SANZHE\":null,\"BUJIMIAN_SANZHE\":null,\"DAOQIANG\":null,\"BUJIMIAN_DAOQIANG\":null,\"SIJI\":\"3\",\"BUJIMIAN_SIJI\":null,\"CHENGKE\":null,\"BUJIMIAN_CHENGKE\":null,\"LOSTSPECIAL\":null,\"BUJIMIAN_LOSTSPECIAL\":null,\"SHESHUI\":\"1\",\"BUJIMIAN_SHESHUI\":null,\"BOLI\":null,\"HUAHEN\":null,\"BUJIMIAN_HUAHEN\":null,\"ZIRAN\":null,\"BUJIMIAN_ZIRAN\":null,\"TAKESPECAILREPAIR\":null,\"SANZHEHOLIDAYDOUBLE\":null}', 1, '2019-03-06 19:08:15', '2019-03-06 19:08:15', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (195, '/api/admin/config/recommend/update/base', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Controllers/Admin/ConfigController.php', 332, '输入类型必须为整数', '{\"FORCEINSURANCE\":null,\"TAXPAY\":null,\"CHESUN\":\"1\",\"BUJIMIAN_CHESUN\":null,\"SANZHE\":null,\"BUJIMIAN_SANZHE\":null,\"DAOQIANG\":null,\"BUJIMIAN_DAOQIANG\":null,\"SIJI\":\"2\",\"BUJIMIAN_SIJI\":null,\"CHENGKE\":null,\"BUJIMIAN_CHENGKE\":null,\"LOSTSPECIAL\":null,\"BUJIMIAN_LOSTSPECIAL\":null,\"SHESHUI\":null,\"BUJIMIAN_SHESHUI\":null,\"BOLI\":null,\"HUAHEN\":null,\"BUJIMIAN_HUAHEN\":null,\"ZIRAN\":null,\"BUJIMIAN_ZIRAN\":null,\"TAKESPECAILREPAIR\":null,\"SANZHEHOLIDAYDOUBLE\":null}', 1, '2019-03-06 19:09:21', '2019-03-06 19:09:21', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (196, '/api/admin/config/recommend/full/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Operation timed out [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/base\"}', 1, '2019-03-06 20:54:50', '2019-03-06 20:54:50', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (197, '/api/admin/config/recommend/full/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Host is down [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/base\"}', 1, '2019-03-06 20:54:51', '2019-03-06 20:54:51', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (198, '/api/admin/config/recommend/full/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Host is down [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/base\"}', 1, '2019-03-06 20:54:51', '2019-03-06 20:54:51', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (199, '/api/admin/config/recommend/full/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Operation timed out [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/base\"}', 1, '2019-03-06 20:54:54', '2019-03-06 20:54:54', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (200, '/api/admin/config/recommend/full/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Operation timed out [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/base\"}', 1, '2019-03-06 20:54:55', '2019-03-06 20:54:55', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (201, '/api/admin/config/recommend/full/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Operation timed out [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/full\\/base\"}', 1, '2019-03-06 20:55:24', '2019-03-06 20:55:24', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (202, '/api/admin/config/recommend/hot', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Operation timed out [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/hot\"}', 1, '2019-03-07 21:09:25', '2019-03-07 21:09:25', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (203, '/api/admin/config/recommend/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Operation timed out [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/base\"}', 1, '2019-03-07 21:09:25', '2019-03-07 21:09:25', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (204, '/api/admin/config/recommend/best', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Operation timed out [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/best\"}', 1, '2019-03-07 21:09:25', '2019-03-07 21:09:25', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (205, '/api/admin/config/recommend/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Host is down [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/base\"}', 1, '2019-03-07 21:09:33', '2019-03-07 21:09:33', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (206, '/api/admin/config/recommend/hot', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Host is down [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/hot\"}', 1, '2019-03-07 21:09:33', '2019-03-07 21:09:33', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (207, '/api/admin/config/recommend/best', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'No route to host [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/best\"}', 1, '2019-03-07 21:09:33', '2019-03-07 21:09:33', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (208, '/api/admin/config/recommend/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Operation timed out [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/base\"}', 1, '2019-03-07 22:24:21', '2019-03-07 22:24:21', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (209, '/api/admin/config/recommend/hot', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Operation timed out [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/hot\"}', 1, '2019-03-07 22:24:21', '2019-03-07 22:24:21', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (210, '/api/admin/config/recommend/best', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Operation timed out [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/best\"}', 1, '2019-03-07 22:24:21', '2019-03-07 22:24:21', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (211, '/api/admin/config/recommend/base', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'No route to host [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/base\"}', 1, '2019-03-07 22:24:22', '2019-03-07 22:24:22', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (212, '/api/admin/config/recommend/best', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Host is down [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/best\"}', 1, '2019-03-07 22:24:22', '2019-03-07 22:24:22', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (213, '/api/admin/config/recommend/hot', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/predis/predis/src/Connection/AbstractConnection.php', 155, 'Host is down [tcp://192.168.1.20:6379]', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/hot\"}', 1, '2019-03-07 22:24:22', '2019-03-07 22:24:22', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (214, '/aa', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:06:28', '2019-03-08 14:06:28', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (215, '/aa', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:11:19', '2019-03-08 14:11:19', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (216, '/aa', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:11:21', '2019-03-08 14:11:21', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (217, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:11:27', '2019-03-08 14:11:27', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (218, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:11', '2019-03-08 14:15:11', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (219, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:14', '2019-03-08 14:15:14', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (220, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:15', '2019-03-08 14:15:15', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (221, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:16', '2019-03-08 14:15:16', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (222, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:17', '2019-03-08 14:15:17', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (223, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:18', '2019-03-08 14:15:18', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (224, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:20', '2019-03-08 14:15:20', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (225, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:20', '2019-03-08 14:15:20', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (226, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:21', '2019-03-08 14:15:21', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (227, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:21', '2019-03-08 14:15:21', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (228, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:22', '2019-03-08 14:15:22', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (229, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:22', '2019-03-08 14:15:22', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (230, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:22', '2019-03-08 14:15:22', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (231, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:23', '2019-03-08 14:15:23', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (232, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:23', '2019-03-08 14:15:23', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (233, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:23', '2019-03-08 14:15:23', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (234, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:23', '2019-03-08 14:15:23', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (235, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:23', '2019-03-08 14:15:23', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (236, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:23', '2019-03-08 14:15:23', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (237, '/api/ss', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 14:15:24', '2019-03-08 14:15:24', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (238, '/api/admin/coupon/retrieveall?type=2', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[HY000] [2002] Network is unreachable (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phptype=2, 664, SQLSTATE[HY000] [2002] Network is unreachable (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phptype=2, 664, SQLSTATE[HY000] [2002] Network is unreachable (SQL: select * from `baoxian_coupon_plan` where (`type` = 2) order by `id` desc), {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"type\":\"2\"}, 1, GET, 1, 2019-03-08 14:38:44, 2019-03-08 14:38:44, {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"type\":\"2\"})), 1, GET, 1, 2019-03-08 14:38:48, 2019-03-08 14:38:48, ?, ?))', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"type\":\"2\"}', 1, '2019-03-08 14:38:52', '2019-03-08 14:38:52', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (239, '/api/admin/company/retrieveall?page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[HY000] [2002] Network is unreachable (SQL: select count(*) as aggregate from `baoxian_company`)', '{\"s\":\"\\/api\\/admin\\/company\\/retrieveall\",\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-08 14:38:44', '2019-03-08 14:38:44', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (240, '/api/admin/coupon/retrieveall?type=1', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[HY000] [2002] Network is unreachable (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/coupon/retrieveall/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.phptype=1, 664, SQLSTATE[HY000] [2002] Network is unreachable (SQL: select * from `baoxian_coupon_plan` where (`type` = 1) order by `id` desc), {\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"type\":\"1\"}, 1, GET, 1, 2019-03-08 14:38:44, 2019-03-08 14:38:44, ?))', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"type\":\"1\"}', 1, '2019-03-08 14:38:51', '2019-03-08 14:38:51', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (241, '/api/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:19:54', '2019-03-08 16:19:54', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (242, '/api/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:20:59', '2019-03-08 16:20:59', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (243, '/api/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:21:08', '2019-03-08 16:21:08', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (244, '/api/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:21:40', '2019-03-08 16:21:40', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (245, '/api/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:24:05', '2019-03-08 16:24:05', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (246, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:31:45', '2019-03-08 16:31:45', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (247, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:32:12', '2019-03-08 16:32:12', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (248, '/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:32:32', '2019-03-08 16:32:32', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (249, '/login/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:33:03', '2019-03-08 16:33:03', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (250, '/login/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:33:32', '2019-03-08 16:33:32', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (251, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:33:40', '2019-03-08 16:33:40', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (252, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:34:15', '2019-03-08 16:34:15', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (253, '/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:34:26', '2019-03-08 16:34:26', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (254, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:35:03', '2019-03-08 16:35:03', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (255, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:38:34', '2019-03-08 16:38:34', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (256, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:38:51', '2019-03-08 16:38:51', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (257, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:39:13', '2019-03-08 16:39:13', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (258, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:40:23', '2019-03-08 16:40:23', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (259, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:42:59', '2019-03-08 16:42:59', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (260, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:43:33', '2019-03-08 16:43:33', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (261, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:43:57', '2019-03-08 16:43:57', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (262, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:47:36', '2019-03-08 16:47:36', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (263, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:48:21', '2019-03-08 16:48:21', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (264, '/api/admin/login', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '[]', 1, '2019-03-08 16:48:24', '2019-03-08 16:48:24', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (265, '/api/getreinfo?LicenseNo=%E8%B4%B5A11111', '/home/vagrant/Code/cmbx/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"LicenseNo\":\"\\u8d35A11111\"}', 1, '2019-03-08 16:53:01', '2019-03-08 16:53:01', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (266, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 179, '', '{\"mobile\":\"15085069257\",\"password\":\"yklykl\"}', 1, '2019-03-11 09:06:02', '2019-03-11 09:06:02', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (267, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/guzzlehttp/guzzle/src/Client.php', 62, 'Argument 1 passed to GuzzleHttp\\Client::__construct() must be of the type array, string given, called in /Users/yangkunlin/git/chemi_api_insurance.com/app/Services/HttpUtil.php on line 25', '{\"mobile\":\"15085069257\",\"password\":\"yklykl\"}', 1, '2019-03-11 09:08:01', '2019-03-11 09:08:01', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (268, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Services/HttpUtil.php', 140, '签名为空', '{\"mobile\":\"15085069257\",\"password\":\"yklykl\"}', 1, '2019-03-11 09:09:37', '2019-03-11 09:09:37', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (269, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Database/Connection.php', 664, 'SQLSTATE[HY000] [2002] Operation timed out (SQL: insert into `baoxian_error` (`url`, `files`, `lines`, `message`, `params`, `status`, `method`, `from`, `update_time`, `create_time`) values (/api/admin/login, /Users/yangkunlin/git/chemi_api_insurance.com/app/Services/Login.php, 29, Call to undefined function App\\Services\\SignatureService(), {\"mobile\":\"15085069257\",\"password\":\"yklykl\"}, 1, POST, 1, 2019-03-11 09:29:56, 2019-03-11 09:29:56))', '{\"mobile\":\"15085069257\",\"password\":\"yklykl\"}', 1, '2019-03-11 09:30:26', '2019-03-11 09:30:26', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (270, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Services/Login.php', 30, 'Call to undefined function App\\Services\\SignatureService()', '{\"mobile\":\"15085069257\",\"password\":\"yklykl\"}', 1, '2019-03-11 09:34:47', '2019-03-11 09:34:47', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (271, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Services/HttpUtil.php', 140, '用户名或密码错误，您还可以登录 9 次！', '{\"mobile\":\"15085069257\",\"password\":\"yklykl\"}', 1, '2019-03-11 09:35:02', '2019-03-11 09:35:02', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (272, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Services/HttpUtil.php', 140, '用户名或密码错误，您还可以登录 8 次！', '{\"mobile\":\"15085069257\",\"password\":\"yklykl\"}', 1, '2019-03-11 09:35:08', '2019-03-11 09:35:08', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (273, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Services/HttpUtil.php', 140, '用户名或密码错误，您还可以登录 7 次！', '{\"mobile\":\"15085069257\",\"password\":\"yklykl\"}', 1, '2019-03-11 09:35:15', '2019-03-11 09:35:15', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (274, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Http/Request.php', 478, 'Session store not set on request.', '{\"mobile\":\"15085069257\",\"password\":\"yklykl\"}', 1, '2019-03-11 09:53:49', '2019-03-11 09:53:49', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (275, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Services/HttpUtil.php', 140, '用户名或密码错误，您还可以登录 9 次！', '{\"mobile\":\"15085069257\",\"password\":\"yklykl\"}', 1, '2019-03-11 10:01:20', '2019-03-11 10:01:20', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (276, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Services/HttpUtil.php', 140, '用户名或密码错误，您还可以登录 8 次！', '{\"mobile\":\"15085069257\",\"password\":\"123123\"}', 1, '2019-03-11 10:01:40', '2019-03-11 10:01:40', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (277, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Services/HttpUtil.php', 140, '用户名或密码错误，您还可以登录 7 次！', '{\"mobile\":\"15085069257\",\"password\":\"123123\"}', 1, '2019-03-11 10:02:04', '2019-03-11 10:02:04', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (278, '/api/admin/login', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Services/HttpUtil.php', 140, '权限不足', '{\"mobile\":\"15085069257\",\"password\":\"123123\"}', 1, '2019-03-11 10:03:50', '2019-03-11 10:03:50', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (279, '/api/admin/coupon/retrieveall?name=&page=1&limit=20', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Middleware/AdminLoginMiddleware.php', 27, '用户未登录', '{\"s\":\"\\/api\\/admin\\/coupon\\/retrieveall\",\"name\":null,\"page\":\"1\",\"limit\":\"20\"}', 1, '2019-03-11 10:13:23', '2019-03-11 10:13:23', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (280, '/api/admin/config/recommend/base', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Middleware/AdminLoginMiddleware.php', 27, '用户未登录', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/base\"}', 1, '2019-03-11 10:20:08', '2019-03-11 10:20:08', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (281, '/api/admin/config/recommend/hot', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Middleware/AdminLoginMiddleware.php', 27, '用户未登录', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/hot\"}', 1, '2019-03-11 10:20:08', '2019-03-11 10:20:08', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (282, '/api/admin/config/recommend/best', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Middleware/AdminLoginMiddleware.php', 27, '用户未登录', '{\"s\":\"\\/api\\/admin\\/config\\/recommend\\/best\"}', 1, '2019-03-11 10:20:08', '2019-03-11 10:20:08', 'GET', 1);
INSERT INTO `baoxian_error` VALUES (283, '/api/admin/login/info', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-11 11:34:13', '2019-03-11 11:34:13', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (284, '/api/admin/login/info', '/Users/yangkunlin/git/chemi_api_insurance.com/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php', 255, '', '[]', 1, '2019-03-11 11:35:49', '2019-03-11 11:35:49', 'POST', 1);
INSERT INTO `baoxian_error` VALUES (285, '/api/admin/login/info', '/Users/yangkunlin/git/chemi_api_insurance.com/app/Http/Middleware/AdminLoginMiddleware.php', 27, '用户未登录', '{\"s\":\"\\/api\\/admin\\/login\\/info\"}', 1, '2019-03-11 11:36:02', '2019-03-11 11:36:02', 'GET', 1);

-- ----------------------------
-- Table structure for baoxian_login
-- ----------------------------
DROP TABLE IF EXISTS `baoxian_login`;
CREATE TABLE `baoxian_login`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '车秘用户ID',
  `type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '绑定类型wx,qq等',
  `authcode` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '平台唯一授权码',
  `openid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '微信openid',
  `nickname` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '昵称',
  `created_at` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`authcode`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '第三方平台绑定' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of baoxian_login
-- ----------------------------
INSERT INTO `baoxian_login` VALUES (1, 60351, 'wx', 'ohQOo1FyvSIfUxqN7u56mp0NSKPA', 'ohQOo1FyvSIfUxqN7u56mp0NSKPA', '', '2019-01-18 14:32:47');
INSERT INTO `baoxian_login` VALUES (2, 48225, 'wx', 'ohQOo1Puy08Q_NasJ-cW1eG8mw7I', 'ohQOo1Puy08Q_NasJ-cW1eG8mw7I', '', '2019-01-18 15:13:18');
INSERT INTO `baoxian_login` VALUES (3, 0, 'wx', 'ohQOo1PzB6hGQyWxlJoIQVOH41dw', 'ohQOo1PzB6hGQyWxlJoIQVOH41dw', '', '2019-01-18 15:40:02');
INSERT INTO `baoxian_login` VALUES (4, 58274, 'wx', 'ohQOo1G2ufsqbbQaRcH5OyMMQKMk', 'ohQOo1G2ufsqbbQaRcH5OyMMQKMk', '', '2019-01-18 16:25:52');
INSERT INTO `baoxian_login` VALUES (5, 47489, 'wx', 'ohQOo1NeZxgi0lUSdDjWTboC8Mto', 'ohQOo1NeZxgi0lUSdDjWTboC8Mto', '', '2019-01-21 09:56:55');

-- ----------------------------
-- Table structure for baoxian_order_page_201903
-- ----------------------------
DROP TABLE IF EXISTS `baoxian_order_page_201903`;
CREATE TABLE `baoxian_order_page_201903`  (
  `id` int(10) UNSIGNED NOT NULL COMMENT '订单ID',
  `uid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `pay` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '订单支付金额：分，订单实际金额 = pay + deduct',
  `deduct` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '抵扣金额：分',
  `payway` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '支付方式：wx微信 cb车币',
  `status` tinyint(1) NULL DEFAULT NULL COMMENT '订单状态：-1已退款 0未支付 1已支付 2已取消',
  `target` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '来源',
  `agent_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '代理人ID',
  `create_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单创建时间',
  `updated_at` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单状态更新时间',
  `citycode` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '城市ID',
  `licenseno` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车牌号',
  `source` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '保司',
  `engineno` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '发动机号',
  `carvin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车架号',
  `registerdate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '注册日期',
  `modlename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '品牌型号',
  `forcetax` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '0:单商业，1：商业+交强车船，2：单交强+车船',
  `forcetimestamp` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '交强险起保时间',
  `biztimestamp` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '商业险起保时间（如果在单商业的情况下 ，此字段必须有值）',
  `boli` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '玻璃单独破碎险，0-不投保，1国产，2进口',
  `bujimianchesun` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '不计免赔险(车损)，0-不投保，1投保',
  `bujimiandaoqiang` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '不计免赔险(盗抢) ，0-不投保，1投保',
  `bujimiansanzhe` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '不计免赔险(三者) ，0-不投保，1投保',
  `bujimianchengke` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '不计免乘客0-不投保，1投保',
  `bujimiansiji` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '不计免司机0-不投保，1投保',
  `bujimianhuahen` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '不计免划痕0-不投保，1投保',
  `bujimiansheshui` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '不计免涉水0-不投保，1投保',
  `bujimianziran` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '不计免自燃0-不投保，1投保',
  `bujimianjingshensunshi` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '不计免精神损失0-不投保，1投保',
  `sheshui` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '涉水行驶损失险，0-不投保，1投保',
  `huahen` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '车身划痕损失险，0-不投保，>0投保(具体金额)',
  `siji` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '车上人员责任险(司机) ，0-不投保，>0投保(具体金额）',
  `chengke` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '车上人员责任险(乘客) ，0-不投保，>0投保(具体金额)',
  `chesun` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '机动车损失保险，0-不投保，1-投保',
  `daoqiang` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '全车盗抢保险，0-不投保，1-投保',
  `sanzhe` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '第三者责任保险，0-不投保，>0投保(具体金额)',
  `ziran` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '自燃损失险，0-不投保，1投保',
  `hcjingshensunshi` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '精神损失抚慰金责任险（0:不投，>0：保额）（前提是三者，司机，乘客至少有一个投保，保额支持自定义）',
  `hcsanfangteyue` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '机动车损失保险无法找到第三方特约险（0:不投，1：投保）(前提必须上车损险)',
  `shebeisunshi` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '设备损失险 1：投保 0:不投保',
  `bjmshebeisunshi` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '不计免设备损失险 1：投保 0:不投保',
  `isnewcar` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '是否新车（1：新车  2：旧车（默认）；）',
  `cartype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车辆类型',
  `carusedtype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '使用性质',
  `seatcount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '核定载客量',
  `toncount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '核定载质量',
  `transferdate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '过户车日期（yyyy-mm-dd）',
  `purchaseprice` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '购置价格',
  `negotiateprice` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '协商价格',
  `automoldcode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '精友编码',
  `exhaustscale` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '排气量',
  `carownersname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车主姓名',
  `owneridcardtype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车主证件类型',
  `idcard` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车主证件号',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车主邮箱',
  `ownersex` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '车主性别 1男 2女',
  `ownerauthority` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车主证件签发机关',
  `ownernation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车主民族',
  `ownerbirthday` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车主生日 格式为：xxxx-xx-xx',
  `owneraddress` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车主联系地址',
  `insuredtoowner` tinyint(1) NULL DEFAULT NULL COMMENT '被保险人是否同车主 0否 1是',
  `insuredpeople` tinyint(1) NULL DEFAULT NULL COMMENT '被保人类型 1个人 2团体',
  `insuredname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '被保险人姓名',
  `insuredidcard` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '被保险人证件号',
  `insuredidtype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '被保险人证件类型',
  `insuredmobile` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '被保险人手机号',
  `insuredemail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '被保人邮箱',
  `insuredaddress` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '被保险人地址',
  `insuredcertistartdate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '被保险人身份证有效期起期（yyyy-mm-dd北京平安必填）',
  `insuredcertienddate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '被保险人身份证有效期止期（yyyy-mm-dd北京平安必填；长期请标识为：9999-12-31）',
  `insuredsex` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '被保险人性别（身份证采集用）1男2女',
  `insuredauthority` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '被保险人证件签发机关（身份证采集用）',
  `insurednation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '被保险人民族',
  `insuredbirthday` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '被保险人生日（身份证采集用）格式为：xxxx-xx-xx',
  `holdertoowner` tinyint(1) NULL DEFAULT NULL COMMENT '投保人是否同车主 0否 1是',
  `holdertoinsured` tinyint(1) NULL DEFAULT NULL COMMENT '投保人是否同被保人 0否 1是',
  `holderpeople` tinyint(1) NULL DEFAULT NULL COMMENT '投保人类型 1个人 2团体',
  `holderidcard` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投保人证件号',
  `holdername` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投保人姓名',
  `holderidtype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投保人证件类型（类型同被保人）',
  `holdermobile` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投保人手机号',
  `holderemail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投保人邮箱',
  `holderaddress` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投保人地址',
  `holdercertistartdate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投保人身份证有效期起期（yyyy-mm-dd北京平安必填）',
  `holdercertienddate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投保人身份证有效期止期（yyyy-mm-dd北京平安必填；长期请标识为：9999-12-31）',
  `holdersex` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '投保人性别（身份证采集用）1男2女',
  `holderauthority` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投保人证件签发机关',
  `holdernation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投保人民族',
  `holderbirthday` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投保人生日（身份证采集用）格式为：xxxx-xx-xx',
  `forceenddate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '交强险截止日期',
  `businessenddate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '商业险截止日期',
  `biztotal` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '商业险总额',
  `forcetotal` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '交强险总额',
  `taxtotal` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '车船税总额',
  `mailaddress` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '保单邮寄地址',
  `electronicaddress` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '电子保单地址',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of baoxian_order_page_201903
-- ----------------------------
INSERT INTO `baoxian_order_page_201903` VALUES (1000, 48225, 100000, NULL, 'wxpayjs', 1, NULL, NULL, NULL, NULL, NULL, NULL, 4097, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1111', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 10, 10, 10, NULL, NULL);

-- ----------------------------
-- Table structure for baoxian_userinfo
-- ----------------------------
DROP TABLE IF EXISTS `baoxian_userinfo`;
CREATE TABLE `baoxian_userinfo`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `citycode` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '城市ID',
  `licenseno` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车牌号',
  `userinfo` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '车主信息json',
  `savequote` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '上年投保信息json',
  `postprecise` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '投保信息json',
  `preciseprice` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '报价信息json',
  `stockinfo` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '个人补充信息json',
  `updated_at` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `CityCode`(`citycode`, `licenseno`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of baoxian_userinfo
-- ----------------------------
INSERT INTO `baoxian_userinfo` VALUES (1, 95, '贵A11111', '{\"CarUsedType\":1,\"LicenseNo\":\"贵A11111\",\"LicenseOwner\":\"黎志**\",\"PurchasePrice\":122900,\"IdType\":1,\"CredentislasNum\":\"42108319970711****\",\"CityCode\":95,\"EngineNo\":\"JW482137\",\"ModleName\":\"北京现代BH6441YAV多用途乘用车\",\"RegisterDate\":\"2018-11-19\",\"CarVin\":\"LBENUBKC7JS098251\",\"ForceExpireDate\":\"2019-11-19\",\"BusinessExpireDate\":\"2019-11-19\",\"NextForceStartDate\":\"2019-11-19\",\"NextBusinessStartDate\":\"2019-11-19\",\"SeatCount\":5,\"InsuredName\":\"黎志**\",\"InsuredIdType\":1,\"InsuredIdCard\":\"42108319970711****\",\"InsuredMobile\":\"\",\"PostedName\":\"黎志**\",\"HolderIdType\":1,\"HolderIdCard\":\"42108319970711****\",\"HolderMobile\":\"\",\"FuelType\":1,\"ProofType\":0,\"ClauseType\":0,\"LicenseColor\":0,\"RunRegion\":0,\"IsPublic\":2,\"BizNo\":\"\",\"ForceNo\":\"\",\"AutoMoldCode\":\"\"}', '{\"Source\":2,\"CheSun\":118900,\"SanZhe\":1000000,\"DaoQiang\":0,\"SiJi\":0,\"ChengKe\":0,\"BoLi\":0,\"HuaHen\":0,\"SheShui\":1,\"ZiRan\":0,\"HcSanFangTeYue\":1,\"HcJingShenSunShi\":0,\"BuJiMianCheSun\":1,\"BuJiMianSanZhe\":1,\"BuJiMianDaoQiang\":0,\"BuJiMianChengKe\":0,\"BuJiMianSiJi\":0,\"BuJiMianHuaHen\":0,\"BuJiMianSheShui\":1,\"BuJiMianZiRan\":0,\"BuJiMianJingShenSunShi\":0,\"HcXiuLiChangType\":\"\",\"Fybc\":\"\",\"FybcDays\":\"\",\"SheBeiSunShi\":\"\",\"BjmSheBeiSunShi\":\"\"}', NULL, '{\"2\":{\"Company\":\"平安\",\"Msg\":\"chemi_user_keys:-10001:上次报价\\/核保值缓存到当天23:59分，请重新请求报价\\/核保再获取详情。\",\"UserInfo\":[],\"Item\":[]},\"4\":{\"Company\":\"人保\",\"Msg\":\"chemi_user_keys:-10001:上次报价\\/核保值缓存到当天23:59分，请重新请求报价\\/核保再获取详情。\",\"UserInfo\":[],\"Item\":[]}}', NULL, 1550560192, 1548660191);

-- ----------------------------
-- Table structure for parkwash_car_brand
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_car_brand`;
CREATE TABLE `parkwash_car_brand`  (
  `id` mediumint(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '品牌名称',
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT 'logo地址',
  `pinyin` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '拼音首字母',
  `ishot` tinyint(1) NULL DEFAULT 0 COMMENT '是否热门',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 22829 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '车辆品牌' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of parkwash_car_brand
-- ----------------------------
INSERT INTO `parkwash_car_brand` VALUES (1, '斯柯达', 'static/brand/1.png', 'S', 0);
INSERT INTO `parkwash_car_brand` VALUES (265, '现代', 'static/brand/265.png', 'X', 0);
INSERT INTO `parkwash_car_brand` VALUES (620, '大众', 'static/brand/620.png', 'D', 1);
INSERT INTO `parkwash_car_brand` VALUES (1484, '丰田', 'static/brand/1484.png', 'F', 1);
INSERT INTO `parkwash_car_brand` VALUES (1872, '福特', 'static/brand/1872.png', 'F', 1);
INSERT INTO `parkwash_car_brand` VALUES (2561, '马自达', 'static/brand/2561.png', 'M', 1);
INSERT INTO `parkwash_car_brand` VALUES (2937, '沃尔沃', 'static/brand/2937.png', 'W', 0);
INSERT INTO `parkwash_car_brand` VALUES (3226, '日产', 'static/brand/3226.png', 'R', 0);
INSERT INTO `parkwash_car_brand` VALUES (3522, '奔驰', 'static/brand/3522.png', 'B', 1);
INSERT INTO `parkwash_car_brand` VALUES (3964, '海马', 'static/brand/3964.png', 'H', 0);
INSERT INTO `parkwash_car_brand` VALUES (4257, '比亚迪', 'static/brand/4257.png', 'B', 0);
INSERT INTO `parkwash_car_brand` VALUES (4595, '奥迪', 'static/brand/4595.png', 'A', 1);
INSERT INTO `parkwash_car_brand` VALUES (5204, '标致', 'static/brand/5204.png', 'B', 0);
INSERT INTO `parkwash_car_brand` VALUES (5538, '铃木', 'static/brand/5538.png', 'L', 0);
INSERT INTO `parkwash_car_brand` VALUES (5829, '本田', 'static/brand/5829.png', 'B', 1);
INSERT INTO `parkwash_car_brand` VALUES (5963, '起亚', 'static/brand/5963.png', 'Q', 0);
INSERT INTO `parkwash_car_brand` VALUES (6280, '宝马', 'static/brand/6280.png', 'B', 1);
INSERT INTO `parkwash_car_brand` VALUES (6792, '雪铁龙', 'static/brand/6792.png', 'X', 0);
INSERT INTO `parkwash_car_brand` VALUES (7747, '路虎', 'static/brand/7747.png', 'L', 0);
INSERT INTO `parkwash_car_brand` VALUES (7941, '捷豹', 'static/brand/7941.png', 'J', 0);
INSERT INTO `parkwash_car_brand` VALUES (8806, '别克', 'static/brand/8806.png', 'B', 1);
INSERT INTO `parkwash_car_brand` VALUES (9194, '荣威', 'static/brand/9194.png', 'R', 0);
INSERT INTO `parkwash_car_brand` VALUES (9350, '雪佛兰', 'static/brand/9350.png', 'X', 1);
INSERT INTO `parkwash_car_brand` VALUES (9681, 'MG', 'static/brand/9681.png', 'M', 0);
INSERT INTO `parkwash_car_brand` VALUES (9996, '雷诺', 'static/brand/9996.png', 'L', 0);
INSERT INTO `parkwash_car_brand` VALUES (10092, '道奇', 'static/brand/10092.png', 'D', 0);
INSERT INTO `parkwash_car_brand` VALUES (10109, '克莱斯勒', 'static/brand/10109.png', 'K', 0);
INSERT INTO `parkwash_car_brand` VALUES (10132, 'Jeep', 'static/brand/10132.png', 'J', 0);
INSERT INTO `parkwash_car_brand` VALUES (10383, 'DS', 'static/brand/10383.png', 'D', 0);
INSERT INTO `parkwash_car_brand` VALUES (10434, '菲亚特', 'static/brand/10434.png', 'F', 0);
INSERT INTO `parkwash_car_brand` VALUES (10502, '凯迪拉克', 'static/brand/10502.png', 'K', 0);
INSERT INTO `parkwash_car_brand` VALUES (10690, '保时捷', 'static/brand/10690.png', 'B', 0);
INSERT INTO `parkwash_car_brand` VALUES (10691, '奔腾', 'static/brand/10691.png', 'B', 0);
INSERT INTO `parkwash_car_brand` VALUES (10692, '长城', 'static/brand/10692.png', 'C', 0);
INSERT INTO `parkwash_car_brand` VALUES (10693, '大通', 'static/brand/10693.png', 'D', 0);
INSERT INTO `parkwash_car_brand` VALUES (10694, '三菱', 'static/brand/10694.png', 'S', 0);
INSERT INTO `parkwash_car_brand` VALUES (10697, '五菱', 'static/brand/10697.png', 'W', 0);
INSERT INTO `parkwash_car_brand` VALUES (10698, '英菲尼迪', 'static/brand/10698.png', 'Y', 0);
INSERT INTO `parkwash_car_brand` VALUES (10699, '中华', 'static/brand/10699.png', 'Z', 0);
INSERT INTO `parkwash_car_brand` VALUES (10701, '雷克萨斯', 'static/brand/10701.png', 'L', 0);
INSERT INTO `parkwash_car_brand` VALUES (11017, '奇瑞汽车', 'static/brand/11017.png', 'Q', 0);
INSERT INTO `parkwash_car_brand` VALUES (11704, '金杯', 'static/brand/11704.png', 'J', 0);
INSERT INTO `parkwash_car_brand` VALUES (12169, '长安汽车', 'static/brand/12169.png', 'C', 0);
INSERT INTO `parkwash_car_brand` VALUES (12489, '北京汽车', 'static/brand/12489.png', 'B', 0);
INSERT INTO `parkwash_car_brand` VALUES (12572, '宝骏', 'static/brand/12572.png', 'B', 0);
INSERT INTO `parkwash_car_brand` VALUES (12688, '讴歌', 'static/brand/12688.png', 'O', 0);
INSERT INTO `parkwash_car_brand` VALUES (12749, '奔驰 Smart', 'static/brand/12749.png', 'B', 0);
INSERT INTO `parkwash_car_brand` VALUES (12827, '传祺', 'static/brand/12827.png', 'C', 0);
INSERT INTO `parkwash_car_brand` VALUES (13447, '启辰', 'static/brand/13447.png', 'Q', 0);
INSERT INTO `parkwash_car_brand` VALUES (13520, '华泰', 'static/brand/13520.png', 'H', 0);
INSERT INTO `parkwash_car_brand` VALUES (13635, '一汽吉林', 'static/brand/13635.png', 'Y', 0);
INSERT INTO `parkwash_car_brand` VALUES (14335, '东风风行', 'static/brand/14335.png', 'D', 0);
INSERT INTO `parkwash_car_brand` VALUES (14784, '纳智捷', 'static/brand/14784.png', 'N', 0);
INSERT INTO `parkwash_car_brand` VALUES (14876, '吉利汽车', 'static/brand/14876.png', 'J', 0);
INSERT INTO `parkwash_car_brand` VALUES (15225, '东南汽车', 'static/brand/15225.png', 'D', 0);
INSERT INTO `parkwash_car_brand` VALUES (15788, '北汽幻速', 'static/brand/15788.png', 'B', 0);
INSERT INTO `parkwash_car_brand` VALUES (16627, 'MINI', 'static/brand/16627.png', 'M', 0);
INSERT INTO `parkwash_car_brand` VALUES (16774, '力帆', 'static/brand/16774.png', 'L', 0);
INSERT INTO `parkwash_car_brand` VALUES (17681, '东风小康', 'static/brand/17681.png', 'D', 0);
INSERT INTO `parkwash_car_brand` VALUES (17898, '江淮', 'static/brand/17898.png', 'J', 0);
INSERT INTO `parkwash_car_brand` VALUES (18591, '众泰', 'static/brand/18591.png', 'Z', 0);
INSERT INTO `parkwash_car_brand` VALUES (18950, '东风风神', 'static/brand/18950.png', 'D', 0);
INSERT INTO `parkwash_car_brand` VALUES (19185, '北汽威旺', 'static/brand/19185.png', 'B', 0);
INSERT INTO `parkwash_car_brand` VALUES (19957, '红旗', 'static/brand/19957.png', 'H', 0);
INSERT INTO `parkwash_car_brand` VALUES (22828, '林肯', 'static/brand/22828.png', 'L', 0);

-- ----------------------------
-- Table structure for parkwash_car_series
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_car_series`;
CREATE TABLE `parkwash_car_series`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `brand_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '品牌ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车系名称',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `brand_id`(`brand_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1522 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '车系' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of parkwash_car_series
-- ----------------------------
INSERT INTO `parkwash_car_series` VALUES (1, 1, '昊锐');
INSERT INTO `parkwash_car_series` VALUES (2, 1, '晶锐');
INSERT INTO `parkwash_car_series` VALUES (3, 1, '明锐');
INSERT INTO `parkwash_car_series` VALUES (4, 1, '明锐RS');
INSERT INTO `parkwash_car_series` VALUES (5, 1, '昕动');
INSERT INTO `parkwash_car_series` VALUES (6, 1, '昕锐');
INSERT INTO `parkwash_car_series` VALUES (7, 1, '速派');
INSERT INTO `parkwash_car_series` VALUES (8, 1, '野帝');
INSERT INTO `parkwash_car_series` VALUES (9, 1, '柯迪亚克');
INSERT INTO `parkwash_car_series` VALUES (10, 1, '速尊');
INSERT INTO `parkwash_car_series` VALUES (11, 1, '昊锐Combi');
INSERT INTO `parkwash_car_series` VALUES (12, 1, '野帝yeti');
INSERT INTO `parkwash_car_series` VALUES (13, 1, 'Fabia Combi 晶锐 旅行车');
INSERT INTO `parkwash_car_series` VALUES (14, 1, 'Fabia-Hatchback 晶锐-两厢');
INSERT INTO `parkwash_car_series` VALUES (15, 1, 'Octavia Combi 明锐 旅行车');
INSERT INTO `parkwash_car_series` VALUES (16, 1, 'Octavia RS Combi 明锐 RS 旅行版');
INSERT INTO `parkwash_car_series` VALUES (17, 1, 'Octavia RS Sport 明锐 RS 运动型');
INSERT INTO `parkwash_car_series` VALUES (18, 1, 'Octavia 明锐');
INSERT INTO `parkwash_car_series` VALUES (19, 1, 'Superb 速派');
INSERT INTO `parkwash_car_series` VALUES (20, 265, 'i30');
INSERT INTO `parkwash_car_series` VALUES (21, 265, 'ix25');
INSERT INTO `parkwash_car_series` VALUES (22, 265, 'ix35');
INSERT INTO `parkwash_car_series` VALUES (23, 265, '朗动');
INSERT INTO `parkwash_car_series` VALUES (24, 265, '名驭');
INSERT INTO `parkwash_car_series` VALUES (25, 265, '瑞纳');
INSERT INTO `parkwash_car_series` VALUES (26, 265, '瑞奕');
INSERT INTO `parkwash_car_series` VALUES (27, 265, '全新胜达');
INSERT INTO `parkwash_car_series` VALUES (28, 265, '索纳塔');
INSERT INTO `parkwash_car_series` VALUES (29, 265, '索纳塔八');
INSERT INTO `parkwash_car_series` VALUES (30, 265, '索纳塔九');
INSERT INTO `parkwash_car_series` VALUES (31, 265, '途胜');
INSERT INTO `parkwash_car_series` VALUES (32, 265, '悦动');
INSERT INTO `parkwash_car_series` VALUES (33, 265, '雅绅特');
INSERT INTO `parkwash_car_series` VALUES (34, 265, '伊兰特');
INSERT INTO `parkwash_car_series` VALUES (35, 265, '名图');
INSERT INTO `parkwash_car_series` VALUES (36, 265, '领动');
INSERT INTO `parkwash_car_series` VALUES (37, 265, '悦纳');
INSERT INTO `parkwash_car_series` VALUES (38, 265, '御翔');
INSERT INTO `parkwash_car_series` VALUES (39, 265, '领翔');
INSERT INTO `parkwash_car_series` VALUES (40, 265, 'Sonata混合动力');
INSERT INTO `parkwash_car_series` VALUES (41, 265, 'Veloster飞思');
INSERT INTO `parkwash_car_series` VALUES (42, 265, '格锐');
INSERT INTO `parkwash_car_series` VALUES (43, 265, '劳恩斯');
INSERT INTO `parkwash_car_series` VALUES (44, 265, '劳恩斯-酷派');
INSERT INTO `parkwash_car_series` VALUES (45, 265, '全新胜达');
INSERT INTO `parkwash_car_series` VALUES (46, 265, '维拉克斯');
INSERT INTO `parkwash_car_series` VALUES (47, 265, '雅科仕');
INSERT INTO `parkwash_car_series` VALUES (48, 265, '雅尊');
INSERT INTO `parkwash_car_series` VALUES (49, 265, '辉翼');
INSERT INTO `parkwash_car_series` VALUES (50, 265, '捷恩斯');
INSERT INTO `parkwash_car_series` VALUES (51, 265, '雅绅特');
INSERT INTO `parkwash_car_series` VALUES (52, 265, '伊兰特');
INSERT INTO `parkwash_car_series` VALUES (53, 265, '格越');
INSERT INTO `parkwash_car_series` VALUES (54, 265, 'i10');
INSERT INTO `parkwash_car_series` VALUES (55, 265, 'i20');
INSERT INTO `parkwash_car_series` VALUES (56, 265, 'i30');
INSERT INTO `parkwash_car_series` VALUES (57, 265, 'i45');
INSERT INTO `parkwash_car_series` VALUES (58, 265, 'i800');
INSERT INTO `parkwash_car_series` VALUES (59, 265, 'ix35');
INSERT INTO `parkwash_car_series` VALUES (60, 265, '索纳塔');
INSERT INTO `parkwash_car_series` VALUES (61, 620, 'Polo');
INSERT INTO `parkwash_car_series` VALUES (62, 620, '帕萨特');
INSERT INTO `parkwash_car_series` VALUES (63, 620, '帕萨特领驭');
INSERT INTO `parkwash_car_series` VALUES (64, 620, '朗境');
INSERT INTO `parkwash_car_series` VALUES (65, 620, '朗行');
INSERT INTO `parkwash_car_series` VALUES (66, 620, '朗逸');
INSERT INTO `parkwash_car_series` VALUES (67, 620, '桑塔纳经典');
INSERT INTO `parkwash_car_series` VALUES (68, 620, '桑塔纳');
INSERT INTO `parkwash_car_series` VALUES (69, 620, '桑塔纳志俊');
INSERT INTO `parkwash_car_series` VALUES (70, 620, '途安');
INSERT INTO `parkwash_car_series` VALUES (71, 620, '途观');
INSERT INTO `parkwash_car_series` VALUES (72, 620, '凌渡');
INSERT INTO `parkwash_car_series` VALUES (73, 620, '途安L');
INSERT INTO `parkwash_car_series` VALUES (74, 620, '辉昂');
INSERT INTO `parkwash_car_series` VALUES (75, 620, '途昂');
INSERT INTO `parkwash_car_series` VALUES (76, 620, '途观L');
INSERT INTO `parkwash_car_series` VALUES (77, 620, '桑塔纳3000');
INSERT INTO `parkwash_car_series` VALUES (78, 620, '宝来');
INSERT INTO `parkwash_car_series` VALUES (79, 620, 'CC');
INSERT INTO `parkwash_car_series` VALUES (80, 620, '捷达');
INSERT INTO `parkwash_car_series` VALUES (81, 620, '速腾');
INSERT INTO `parkwash_car_series` VALUES (82, 620, '高尔夫');
INSERT INTO `parkwash_car_series` VALUES (83, 620, '迈腾');
INSERT INTO `parkwash_car_series` VALUES (84, 620, '高尔夫·嘉旅');
INSERT INTO `parkwash_car_series` VALUES (85, 620, '蔚领');
INSERT INTO `parkwash_car_series` VALUES (86, 620, 'CC');
INSERT INTO `parkwash_car_series` VALUES (87, 620, 'Eos');
INSERT INTO `parkwash_car_series` VALUES (88, 620, 'R36');
INSERT INTO `parkwash_car_series` VALUES (89, 620, '迈特威Multiva');
INSERT INTO `parkwash_car_series` VALUES (90, 620, '迈腾');
INSERT INTO `parkwash_car_series` VALUES (91, 620, '大众up');
INSERT INTO `parkwash_car_series` VALUES (92, 620, '夏朗');
INSERT INTO `parkwash_car_series` VALUES (93, 620, '尚酷');
INSERT INTO `parkwash_car_series` VALUES (94, 620, '甲壳虫');
INSERT INTO `parkwash_car_series` VALUES (95, 620, '辉腾');
INSERT INTO `parkwash_car_series` VALUES (96, 620, '凯路威');
INSERT INTO `parkwash_car_series` VALUES (97, 620, '途观Tiguan');
INSERT INTO `parkwash_car_series` VALUES (98, 620, '途锐');
INSERT INTO `parkwash_car_series` VALUES (99, 620, 'Cross Golf');
INSERT INTO `parkwash_car_series` VALUES (100, 620, '高尔夫');
INSERT INTO `parkwash_car_series` VALUES (101, 620, 'Sportsvan');
INSERT INTO `parkwash_car_series` VALUES (102, 620, '蔚揽');
INSERT INTO `parkwash_car_series` VALUES (103, 620, 'Tiguan');
INSERT INTO `parkwash_car_series` VALUES (104, 620, 'Amarok');
INSERT INTO `parkwash_car_series` VALUES (105, 620, 'Caddy');
INSERT INTO `parkwash_car_series` VALUES (106, 620, 'Routan');
INSERT INTO `parkwash_car_series` VALUES (107, 620, 'POLO');
INSERT INTO `parkwash_car_series` VALUES (108, 620, 'Passat');
INSERT INTO `parkwash_car_series` VALUES (109, 620, 'Jetta5');
INSERT INTO `parkwash_car_series` VALUES (110, 1484, 'RAV4');
INSERT INTO `parkwash_car_series` VALUES (111, 1484, '兰德酷路泽');
INSERT INTO `parkwash_car_series` VALUES (112, 1484, '普锐斯');
INSERT INTO `parkwash_car_series` VALUES (113, 1484, '威驰');
INSERT INTO `parkwash_car_series` VALUES (114, 1484, '普拉多');
INSERT INTO `parkwash_car_series` VALUES (115, 1484, '花冠');
INSERT INTO `parkwash_car_series` VALUES (116, 1484, '锐志');
INSERT INTO `parkwash_car_series` VALUES (117, 1484, '皇冠');
INSERT INTO `parkwash_car_series` VALUES (118, 1484, '卡罗拉');
INSERT INTO `parkwash_car_series` VALUES (119, 1484, '普拉多(进口)');
INSERT INTO `parkwash_car_series` VALUES (120, 1484, '兰德酷路泽(进口)');
INSERT INTO `parkwash_car_series` VALUES (121, 1484, '丰田86');
INSERT INTO `parkwash_car_series` VALUES (122, 1484, 'Previa（进口）');
INSERT INTO `parkwash_car_series` VALUES (123, 1484, 'YARiS L 致炫');
INSERT INTO `parkwash_car_series` VALUES (124, 1484, '雷凌');
INSERT INTO `parkwash_car_series` VALUES (125, 1484, '逸致');
INSERT INTO `parkwash_car_series` VALUES (126, 1484, '凯美瑞');
INSERT INTO `parkwash_car_series` VALUES (127, 1484, '凯美瑞混合动力');
INSERT INTO `parkwash_car_series` VALUES (128, 1484, '汉兰达');
INSERT INTO `parkwash_car_series` VALUES (129, 1484, '雅力士');
INSERT INTO `parkwash_car_series` VALUES (130, 1484, 'FJ酷路泽（进口）');
INSERT INTO `parkwash_car_series` VALUES (131, 1484, 'Venza威飒（进口）');
INSERT INTO `parkwash_car_series` VALUES (132, 1484, '埃尔法（进口）');
INSERT INTO `parkwash_car_series` VALUES (133, 1484, '杰路驰（进口）');
INSERT INTO `parkwash_car_series` VALUES (134, 1484, 'YARiS L 致享');
INSERT INTO `parkwash_car_series` VALUES (135, 1484, '汉兰达（进口）');
INSERT INTO `parkwash_car_series` VALUES (136, 1484, '雅力士（进口）');
INSERT INTO `parkwash_car_series` VALUES (137, 1484, '4Runner');
INSERT INTO `parkwash_car_series` VALUES (138, 1484, 'Avalon（亚洲龙）');
INSERT INTO `parkwash_car_series` VALUES (139, 1484, 'Aygo');
INSERT INTO `parkwash_car_series` VALUES (140, 1484, 'Camry Hybrid（佳美 油电混合）');
INSERT INTO `parkwash_car_series` VALUES (141, 1484, 'Camry（佳美）');
INSERT INTO `parkwash_car_series` VALUES (142, 1484, 'Century（世纪）');
INSERT INTO `parkwash_car_series` VALUES (143, 1484, 'Fortuner');
INSERT INTO `parkwash_car_series` VALUES (144, 1484, 'Hiace（海狮）');
INSERT INTO `parkwash_car_series` VALUES (145, 1484, 'Hilux');
INSERT INTO `parkwash_car_series` VALUES (146, 1484, 'Sequoia（红杉）');
INSERT INTO `parkwash_car_series` VALUES (147, 1484, 'Sienna（塞纳）');
INSERT INTO `parkwash_car_series` VALUES (148, 1484, 'Solara（速乐娜）');
INSERT INTO `parkwash_car_series` VALUES (149, 1484, 'Tundra（坦途）');
INSERT INTO `parkwash_car_series` VALUES (150, 1484, 'Wish (小霸王）');
INSERT INTO `parkwash_car_series` VALUES (151, 1484, '皇冠');
INSERT INTO `parkwash_car_series` VALUES (152, 1484, 'RAV4');
INSERT INTO `parkwash_car_series` VALUES (153, 1872, '福克斯');
INSERT INTO `parkwash_car_series` VALUES (154, 1872, '嘉年华');
INSERT INTO `parkwash_car_series` VALUES (155, 1872, '蒙迪欧');
INSERT INTO `parkwash_car_series` VALUES (156, 1872, '蒙迪欧致胜');
INSERT INTO `parkwash_car_series` VALUES (157, 1872, '福睿斯');
INSERT INTO `parkwash_car_series` VALUES (158, 1872, '麦柯斯');
INSERT INTO `parkwash_car_series` VALUES (159, 1872, '翼虎');
INSERT INTO `parkwash_car_series` VALUES (160, 1872, '翼搏');
INSERT INTO `parkwash_car_series` VALUES (161, 1872, '锐界');
INSERT INTO `parkwash_car_series` VALUES (162, 1872, '金牛座');
INSERT INTO `parkwash_car_series` VALUES (163, 1872, 'F-150 猛禽');
INSERT INTO `parkwash_car_series` VALUES (164, 1872, 'F-250');
INSERT INTO `parkwash_car_series` VALUES (165, 1872, 'F-450');
INSERT INTO `parkwash_car_series` VALUES (166, 1872, 'F-550');
INSERT INTO `parkwash_car_series` VALUES (167, 1872, 'Fiesta 嘉年华');
INSERT INTO `parkwash_car_series` VALUES (168, 1872, 'Flex');
INSERT INTO `parkwash_car_series` VALUES (169, 1872, 'Focus 福克斯');
INSERT INTO `parkwash_car_series` VALUES (170, 1872, 'Fusion');
INSERT INTO `parkwash_car_series` VALUES (171, 1872, 'Galaxy');
INSERT INTO `parkwash_car_series` VALUES (172, 1872, 'Ka');
INSERT INTO `parkwash_car_series` VALUES (173, 1872, 'Kuga 翼虎');
INSERT INTO `parkwash_car_series` VALUES (174, 1872, 'Mondeo 蒙迪欧');
INSERT INTO `parkwash_car_series` VALUES (175, 1872, 'Mustang 野马');
INSERT INTO `parkwash_car_series` VALUES (176, 1872, 'S-Max 麦柯斯');
INSERT INTO `parkwash_car_series` VALUES (177, 1872, 'Taurus 金牛座');
INSERT INTO `parkwash_car_series` VALUES (178, 1872, 'Transit Connect 全顺');
INSERT INTO `parkwash_car_series` VALUES (179, 1872, 'C-Max');
INSERT INTO `parkwash_car_series` VALUES (180, 1872, 'E Class');
INSERT INTO `parkwash_car_series` VALUES (181, 1872, 'Econoline 依克诺莱恩');
INSERT INTO `parkwash_car_series` VALUES (182, 1872, 'Edge 爱虎');
INSERT INTO `parkwash_car_series` VALUES (183, 1872, 'Edge 锐界');
INSERT INTO `parkwash_car_series` VALUES (184, 1872, 'Escape 翼虎');
INSERT INTO `parkwash_car_series` VALUES (185, 1872, 'Everest');
INSERT INTO `parkwash_car_series` VALUES (186, 1872, 'Expedition 征服者');
INSERT INTO `parkwash_car_series` VALUES (187, 1872, 'Explorer Sport Trac 探索者皮卡');
INSERT INTO `parkwash_car_series` VALUES (188, 1872, 'Explorer 探索者');
INSERT INTO `parkwash_car_series` VALUES (189, 1872, 'Explorer 探险者');
INSERT INTO `parkwash_car_series` VALUES (190, 2561, '睿翼');
INSERT INTO `parkwash_car_series` VALUES (191, 2561, '阿特兹');
INSERT INTO `parkwash_car_series` VALUES (192, 2561, '马自达6');
INSERT INTO `parkwash_car_series` VALUES (193, 2561, '马自达CX-7');
INSERT INTO `parkwash_car_series` VALUES (194, 2561, '马自达8');
INSERT INTO `parkwash_car_series` VALUES (195, 2561, '马自达CX-7（进口）');
INSERT INTO `parkwash_car_series` VALUES (196, 2561, '马自达CX-9（进口）');
INSERT INTO `parkwash_car_series` VALUES (197, 2561, '马自达5（进口)');
INSERT INTO `parkwash_car_series` VALUES (198, 2561, '马自达MX-5（进口）');
INSERT INTO `parkwash_car_series` VALUES (199, 2561, '马自达CX-4');
INSERT INTO `parkwash_car_series` VALUES (200, 2561, '马自达6（进口）');
INSERT INTO `parkwash_car_series` VALUES (201, 2561, '马自达MPV（进口）');
INSERT INTO `parkwash_car_series` VALUES (202, 2561, '马自达RX-8（进口）');
INSERT INTO `parkwash_car_series` VALUES (203, 2561, 'Tribute（进口）');
INSERT INTO `parkwash_car_series` VALUES (204, 2561, 'Tribute Hybrid（进口）');
INSERT INTO `parkwash_car_series` VALUES (205, 2561, '马自达3');
INSERT INTO `parkwash_car_series` VALUES (206, 2561, '马自达3星骋-三厢');
INSERT INTO `parkwash_car_series` VALUES (207, 2561, '马自达3星骋-两厢');
INSERT INTO `parkwash_car_series` VALUES (208, 2561, '马自达2');
INSERT INTO `parkwash_car_series` VALUES (209, 2561, '马自达2劲翔');
INSERT INTO `parkwash_car_series` VALUES (210, 2561, '马自达2三厢');
INSERT INTO `parkwash_car_series` VALUES (211, 2561, 'CX-5');
INSERT INTO `parkwash_car_series` VALUES (212, 2561, '马自达3 Axela-三厢');
INSERT INTO `parkwash_car_series` VALUES (213, 2561, '马自达3 Axela-两厢');
INSERT INTO `parkwash_car_series` VALUES (214, 2561, 'CX-5（进口）');
INSERT INTO `parkwash_car_series` VALUES (215, 2561, '马自达2（进口）');
INSERT INTO `parkwash_car_series` VALUES (216, 2561, '马自达3（进口）');
INSERT INTO `parkwash_car_series` VALUES (217, 2937, 'S40');
INSERT INTO `parkwash_car_series` VALUES (218, 2937, 'S80L');
INSERT INTO `parkwash_car_series` VALUES (219, 2937, 'S60L');
INSERT INTO `parkwash_car_series` VALUES (220, 2937, 'XC60');
INSERT INTO `parkwash_car_series` VALUES (221, 2937, 'XC CLASSIC');
INSERT INTO `parkwash_car_series` VALUES (222, 2937, 'S90');
INSERT INTO `parkwash_car_series` VALUES (223, 2937, 'V40');
INSERT INTO `parkwash_car_series` VALUES (224, 2937, 'C30');
INSERT INTO `parkwash_car_series` VALUES (225, 2937, 'S60');
INSERT INTO `parkwash_car_series` VALUES (226, 2937, 'V60');
INSERT INTO `parkwash_car_series` VALUES (227, 2937, 'XC60');
INSERT INTO `parkwash_car_series` VALUES (228, 2937, 'XC90');
INSERT INTO `parkwash_car_series` VALUES (229, 2937, 'C70 ');
INSERT INTO `parkwash_car_series` VALUES (230, 2937, 'S90');
INSERT INTO `parkwash_car_series` VALUES (231, 2937, 'S40');
INSERT INTO `parkwash_car_series` VALUES (232, 2937, 'S80');
INSERT INTO `parkwash_car_series` VALUES (233, 2937, 'V70');
INSERT INTO `parkwash_car_series` VALUES (234, 2937, 'V90');
INSERT INTO `parkwash_car_series` VALUES (235, 2937, 'XC70');
INSERT INTO `parkwash_car_series` VALUES (236, 3226, '骊威');
INSERT INTO `parkwash_car_series` VALUES (237, 3226, '楼兰');
INSERT INTO `parkwash_car_series` VALUES (238, 3226, '玛驰');
INSERT INTO `parkwash_car_series` VALUES (239, 3226, '奇骏');
INSERT INTO `parkwash_car_series` VALUES (240, 3226, '骐达');
INSERT INTO `parkwash_car_series` VALUES (241, 3226, '天籁');
INSERT INTO `parkwash_car_series` VALUES (242, 3226, '逍客');
INSERT INTO `parkwash_car_series` VALUES (243, 3226, '阳光');
INSERT INTO `parkwash_car_series` VALUES (244, 3226, '轩逸');
INSERT INTO `parkwash_car_series` VALUES (245, 3226, '颐达');
INSERT INTO `parkwash_car_series` VALUES (246, 3226, '骏逸');
INSERT INTO `parkwash_car_series` VALUES (247, 3226, '蓝鸟');
INSERT INTO `parkwash_car_series` VALUES (248, 3226, '西玛');
INSERT INTO `parkwash_car_series` VALUES (249, 3226, '劲客');
INSERT INTO `parkwash_car_series` VALUES (250, 3226, 'D22皮卡');
INSERT INTO `parkwash_car_series` VALUES (251, 3226, 'ZN6493');
INSERT INTO `parkwash_car_series` VALUES (252, 3226, '帕拉丁');
INSERT INTO `parkwash_car_series` VALUES (253, 3226, '帕拉骐');
INSERT INTO `parkwash_car_series` VALUES (254, 3226, '凯普斯达');
INSERT INTO `parkwash_car_series` VALUES (255, 3226, 'NV200');
INSERT INTO `parkwash_car_series` VALUES (256, 3226, '御轩');
INSERT INTO `parkwash_car_series` VALUES (257, 3226, '奥丁');
INSERT INTO `parkwash_car_series` VALUES (258, 3226, '帅客');
INSERT INTO `parkwash_car_series` VALUES (259, 3226, '风度MX6');
INSERT INTO `parkwash_car_series` VALUES (260, 3226, '风度MX5');
INSERT INTO `parkwash_car_series` VALUES (261, 3226, '纳瓦拉');
INSERT INTO `parkwash_car_series` VALUES (262, 3226, '俊风');
INSERT INTO `parkwash_car_series` VALUES (263, 3226, '锐骐多功能商用车');
INSERT INTO `parkwash_car_series` VALUES (264, 3226, '锐骐皮卡');
INSERT INTO `parkwash_car_series` VALUES (265, 3226, 'Quest');
INSERT INTO `parkwash_car_series` VALUES (266, 3226, 'Patrol');
INSERT INTO `parkwash_car_series` VALUES (267, 3226, '350');
INSERT INTO `parkwash_car_series` VALUES (268, 3226, '350Z Coupe');
INSERT INTO `parkwash_car_series` VALUES (269, 3226, '370Z Coupe');
INSERT INTO `parkwash_car_series` VALUES (270, 3226, '370Z Coupe Nismo');
INSERT INTO `parkwash_car_series` VALUES (271, 3226, '370Z Roadster');
INSERT INTO `parkwash_car_series` VALUES (272, 3226, 'Almera');
INSERT INTO `parkwash_car_series` VALUES (273, 3226, 'Altima');
INSERT INTO `parkwash_car_series` VALUES (274, 3226, 'Armada');
INSERT INTO `parkwash_car_series` VALUES (275, 3226, 'Cima');
INSERT INTO `parkwash_car_series` VALUES (276, 3226, 'Cube');
INSERT INTO `parkwash_car_series` VALUES (277, 3226, 'Fuga 250');
INSERT INTO `parkwash_car_series` VALUES (278, 3226, 'Fuga 350');
INSERT INTO `parkwash_car_series` VALUES (279, 3226, 'Fuga 370');
INSERT INTO `parkwash_car_series` VALUES (280, 3226, 'GT-R');
INSERT INTO `parkwash_car_series` VALUES (281, 3226, 'GT-R Nismo');
INSERT INTO `parkwash_car_series` VALUES (282, 3226, 'March 玛驰');
INSERT INTO `parkwash_car_series` VALUES (283, 3226, 'Maxima');
INSERT INTO `parkwash_car_series` VALUES (284, 3226, 'Micra');
INSERT INTO `parkwash_car_series` VALUES (285, 3226, 'Micra Visio');
INSERT INTO `parkwash_car_series` VALUES (286, 3226, 'Murano');
INSERT INTO `parkwash_car_series` VALUES (287, 3226, 'Paladin');
INSERT INTO `parkwash_car_series` VALUES (288, 3226, 'Pathfinder');
INSERT INTO `parkwash_car_series` VALUES (289, 3226, 'Rogue');
INSERT INTO `parkwash_car_series` VALUES (290, 3226, 'Sentra-U');
INSERT INTO `parkwash_car_series` VALUES (291, 3226, 'Tiida-Hatchback 骐达 两厢');
INSERT INTO `parkwash_car_series` VALUES (292, 3226, 'Tiida-Sedan 骐达 三厢');
INSERT INTO `parkwash_car_series` VALUES (293, 3226, 'Titan King Cab');
INSERT INTO `parkwash_car_series` VALUES (294, 3226, 'X-Trail');
INSERT INTO `parkwash_car_series` VALUES (295, 3522, 'A级');
INSERT INTO `parkwash_car_series` VALUES (296, 3522, 'B级');
INSERT INTO `parkwash_car_series` VALUES (297, 3522, 'CLA级');
INSERT INTO `parkwash_car_series` VALUES (298, 3522, 'CLS级');
INSERT INTO `parkwash_car_series` VALUES (299, 3522, 'C级');
INSERT INTO `parkwash_car_series` VALUES (300, 3522, 'C级AMG');
INSERT INTO `parkwash_car_series` VALUES (301, 3522, 'C级旅行车');
INSERT INTO `parkwash_car_series` VALUES (302, 3522, 'E级');
INSERT INTO `parkwash_car_series` VALUES (303, 3522, 'E级敞篷');
INSERT INTO `parkwash_car_series` VALUES (304, 3522, 'GLK级');
INSERT INTO `parkwash_car_series` VALUES (305, 3522, 'GL级');
INSERT INTO `parkwash_car_series` VALUES (306, 3522, 'G级');
INSERT INTO `parkwash_car_series` VALUES (307, 3522, 'G级AMG');
INSERT INTO `parkwash_car_series` VALUES (308, 3522, 'M级');
INSERT INTO `parkwash_car_series` VALUES (309, 3522, 'M级AMG');
INSERT INTO `parkwash_car_series` VALUES (310, 3522, 'R级');
INSERT INTO `parkwash_car_series` VALUES (311, 3522, 'SLK级');
INSERT INTO `parkwash_car_series` VALUES (312, 3522, 'SLS级AMG');
INSERT INTO `parkwash_car_series` VALUES (313, 3522, 'SL级');
INSERT INTO `parkwash_car_series` VALUES (314, 3522, 'S级');
INSERT INTO `parkwash_car_series` VALUES (315, 3522, 'S级AMG');
INSERT INTO `parkwash_car_series` VALUES (316, 3522, 'S级AMG双门');
INSERT INTO `parkwash_car_series` VALUES (317, 3522, 'S级混合动力');
INSERT INTO `parkwash_car_series` VALUES (318, 3522, 'S级双门');
INSERT INTO `parkwash_car_series` VALUES (319, 3522, '迈巴赫S级');
INSERT INTO `parkwash_car_series` VALUES (320, 3522, 'A级AMG');
INSERT INTO `parkwash_car_series` VALUES (321, 3522, 'GLA级');
INSERT INTO `parkwash_car_series` VALUES (322, 3522, 'GLE级');
INSERT INTO `parkwash_car_series` VALUES (323, 3522, 'GLC级');
INSERT INTO `parkwash_car_series` VALUES (324, 3522, 'GLS级');
INSERT INTO `parkwash_car_series` VALUES (325, 3522, 'GL级AMG');
INSERT INTO `parkwash_car_series` VALUES (326, 3522, 'CLA级AMG');
INSERT INTO `parkwash_car_series` VALUES (327, 3522, 'GLA级AMG');
INSERT INTO `parkwash_car_series` VALUES (328, 3522, 'CLS级AMG');
INSERT INTO `parkwash_car_series` VALUES (329, 3522, 'AMG级GT');
INSERT INTO `parkwash_car_series` VALUES (330, 3522, 'GLE级AMG');
INSERT INTO `parkwash_car_series` VALUES (331, 3522, 'SLC级');
INSERT INTO `parkwash_car_series` VALUES (332, 3522, 'GLS级AMG');
INSERT INTO `parkwash_car_series` VALUES (333, 3522, 'CL级AMG');
INSERT INTO `parkwash_car_series` VALUES (334, 3522, 'CL级');
INSERT INTO `parkwash_car_series` VALUES (335, 3522, 'CLK级AMG');
INSERT INTO `parkwash_car_series` VALUES (336, 3522, 'CLK级');
INSERT INTO `parkwash_car_series` VALUES (337, 3522, 'E级AMG');
INSERT INTO `parkwash_car_series` VALUES (338, 3522, 'GLC级AMG');
INSERT INTO `parkwash_car_series` VALUES (339, 3522, 'R级AMG');
INSERT INTO `parkwash_car_series` VALUES (340, 3522, 'SL级AMG');
INSERT INTO `parkwash_car_series` VALUES (341, 3522, 'SLK级AMG');
INSERT INTO `parkwash_car_series` VALUES (342, 3522, 'SLR级');
INSERT INTO `parkwash_car_series` VALUES (343, 3522, '斯宾特');
INSERT INTO `parkwash_car_series` VALUES (344, 3522, '乌尼莫克');
INSERT INTO `parkwash_car_series` VALUES (345, 3522, '唯雅诺');
INSERT INTO `parkwash_car_series` VALUES (346, 3522, '威霆');
INSERT INTO `parkwash_car_series` VALUES (347, 3522, 'C级');
INSERT INTO `parkwash_car_series` VALUES (348, 3522, 'E级');
INSERT INTO `parkwash_car_series` VALUES (349, 3522, 'GLK级');
INSERT INTO `parkwash_car_series` VALUES (350, 3522, 'GLA级');
INSERT INTO `parkwash_car_series` VALUES (351, 3522, 'GLC级');
INSERT INTO `parkwash_car_series` VALUES (352, 3522, '威霆');
INSERT INTO `parkwash_car_series` VALUES (353, 3522, '唯雅诺');
INSERT INTO `parkwash_car_series` VALUES (354, 3522, '凌特 佳旅');
INSERT INTO `parkwash_car_series` VALUES (355, 3522, '凌特 尊旅');
INSERT INTO `parkwash_car_series` VALUES (356, 3522, '凌特 厢式车');
INSERT INTO `parkwash_car_series` VALUES (357, 3522, '凌特 畅旅');
INSERT INTO `parkwash_car_series` VALUES (358, 3522, 'V级');
INSERT INTO `parkwash_car_series` VALUES (359, 3964, '海马骑士');
INSERT INTO `parkwash_car_series` VALUES (360, 3964, '丘比特');
INSERT INTO `parkwash_car_series` VALUES (361, 3964, '福美来');
INSERT INTO `parkwash_car_series` VALUES (362, 3964, '普力马');
INSERT INTO `parkwash_car_series` VALUES (363, 3964, '欢动');
INSERT INTO `parkwash_car_series` VALUES (364, 3964, '福美来M5');
INSERT INTO `parkwash_car_series` VALUES (365, 3964, '福美来VS');
INSERT INTO `parkwash_car_series` VALUES (366, 3964, '海福星');
INSERT INTO `parkwash_car_series` VALUES (367, 3964, '海马3');
INSERT INTO `parkwash_car_series` VALUES (368, 3964, '海马M3');
INSERT INTO `parkwash_car_series` VALUES (369, 3964, '海马M8');
INSERT INTO `parkwash_car_series` VALUES (370, 3964, '海马S5');
INSERT INTO `parkwash_car_series` VALUES (371, 3964, '海马S7');
INSERT INTO `parkwash_car_series` VALUES (372, 3964, '海马M6');
INSERT INTO `parkwash_car_series` VALUES (373, 3964, '福美来四代');
INSERT INTO `parkwash_car_series` VALUES (374, 3964, 'V70');
INSERT INTO `parkwash_car_series` VALUES (375, 3964, '海马S5 Young');
INSERT INTO `parkwash_car_series` VALUES (376, 3964, '福美来七座');
INSERT INTO `parkwash_car_series` VALUES (377, 3964, '福美来F7');
INSERT INTO `parkwash_car_series` VALUES (378, 3964, '海马王子');
INSERT INTO `parkwash_car_series` VALUES (379, 3964, '爱尚');
INSERT INTO `parkwash_car_series` VALUES (380, 3964, '新鸿达');
INSERT INTO `parkwash_car_series` VALUES (381, 3964, '福仕达');
INSERT INTO `parkwash_car_series` VALUES (382, 3964, '腾达');
INSERT INTO `parkwash_car_series` VALUES (383, 3964, '荣达');
INSERT INTO `parkwash_car_series` VALUES (384, 3964, '海马S5 Young');
INSERT INTO `parkwash_car_series` VALUES (385, 4257, 'F6');
INSERT INTO `parkwash_car_series` VALUES (386, 4257, 'M6');
INSERT INTO `parkwash_car_series` VALUES (387, 4257, 'S7');
INSERT INTO `parkwash_car_series` VALUES (388, 4257, 'G3R');
INSERT INTO `parkwash_car_series` VALUES (389, 4257, 'G6');
INSERT INTO `parkwash_car_series` VALUES (390, 4257, 'F3R');
INSERT INTO `parkwash_car_series` VALUES (391, 4257, 'G5');
INSERT INTO `parkwash_car_series` VALUES (392, 4257, '思锐');
INSERT INTO `parkwash_car_series` VALUES (393, 4257, '秦');
INSERT INTO `parkwash_car_series` VALUES (394, 4257, 'F0');
INSERT INTO `parkwash_car_series` VALUES (395, 4257, 'G3');
INSERT INTO `parkwash_car_series` VALUES (396, 4257, 'F3');
INSERT INTO `parkwash_car_series` VALUES (397, 4257, '速锐');
INSERT INTO `parkwash_car_series` VALUES (398, 4257, 'S6');
INSERT INTO `parkwash_car_series` VALUES (399, 4257, 'L3');
INSERT INTO `parkwash_car_series` VALUES (400, 4257, '宋');
INSERT INTO `parkwash_car_series` VALUES (401, 4257, '元');
INSERT INTO `parkwash_car_series` VALUES (402, 4257, '唐');
INSERT INTO `parkwash_car_series` VALUES (403, 4257, 'e5');
INSERT INTO `parkwash_car_series` VALUES (404, 4257, 'e6');
INSERT INTO `parkwash_car_series` VALUES (405, 4257, 'S8');
INSERT INTO `parkwash_car_series` VALUES (406, 4595, 'A3');
INSERT INTO `parkwash_car_series` VALUES (407, 4595, 'A4');
INSERT INTO `parkwash_car_series` VALUES (408, 4595, 'A4L');
INSERT INTO `parkwash_car_series` VALUES (409, 4595, 'A6L');
INSERT INTO `parkwash_car_series` VALUES (410, 4595, 'Q3');
INSERT INTO `parkwash_car_series` VALUES (411, 4595, 'Q5');
INSERT INTO `parkwash_car_series` VALUES (412, 4595, 'Q5');
INSERT INTO `parkwash_car_series` VALUES (413, 4595, 'A1');
INSERT INTO `parkwash_car_series` VALUES (414, 4595, 'A3');
INSERT INTO `parkwash_car_series` VALUES (415, 4595, 'A4');
INSERT INTO `parkwash_car_series` VALUES (416, 4595, 'A5');
INSERT INTO `parkwash_car_series` VALUES (417, 4595, 'A6');
INSERT INTO `parkwash_car_series` VALUES (418, 4595, 'A7');
INSERT INTO `parkwash_car_series` VALUES (419, 4595, 'A8L');
INSERT INTO `parkwash_car_series` VALUES (420, 4595, 'Q3');
INSERT INTO `parkwash_car_series` VALUES (421, 4595, 'Q7');
INSERT INTO `parkwash_car_series` VALUES (422, 4595, 'S3');
INSERT INTO `parkwash_car_series` VALUES (423, 4595, 'S5');
INSERT INTO `parkwash_car_series` VALUES (424, 4595, 'S6');
INSERT INTO `parkwash_car_series` VALUES (425, 4595, 'S7');
INSERT INTO `parkwash_car_series` VALUES (426, 4595, 'S8');
INSERT INTO `parkwash_car_series` VALUES (427, 4595, 'SQ5');
INSERT INTO `parkwash_car_series` VALUES (428, 4595, 'TT');
INSERT INTO `parkwash_car_series` VALUES (429, 4595, 'TTS');
INSERT INTO `parkwash_car_series` VALUES (430, 4595, 'RS4');
INSERT INTO `parkwash_car_series` VALUES (431, 4595, 'S4');
INSERT INTO `parkwash_car_series` VALUES (432, 4595, 'RS5');
INSERT INTO `parkwash_car_series` VALUES (433, 4595, 'RS6');
INSERT INTO `parkwash_car_series` VALUES (434, 4595, 'RS7');
INSERT INTO `parkwash_car_series` VALUES (435, 4595, 'R8');
INSERT INTO `parkwash_car_series` VALUES (436, 5204, '206');
INSERT INTO `parkwash_car_series` VALUES (437, 5204, 'CROSS 207');
INSERT INTO `parkwash_car_series` VALUES (438, 5204, '207三厢');
INSERT INTO `parkwash_car_series` VALUES (439, 5204, '207两厢');
INSERT INTO `parkwash_car_series` VALUES (440, 5204, '301');
INSERT INTO `parkwash_car_series` VALUES (441, 5204, 'CROSS 307');
INSERT INTO `parkwash_car_series` VALUES (442, 5204, '307');
INSERT INTO `parkwash_car_series` VALUES (443, 5204, '307两厢');
INSERT INTO `parkwash_car_series` VALUES (444, 5204, '307三厢');
INSERT INTO `parkwash_car_series` VALUES (445, 5204, '308');
INSERT INTO `parkwash_car_series` VALUES (446, 5204, '408');
INSERT INTO `parkwash_car_series` VALUES (447, 5204, '508');
INSERT INTO `parkwash_car_series` VALUES (448, 5204, '2008');
INSERT INTO `parkwash_car_series` VALUES (449, 5204, '3008');
INSERT INTO `parkwash_car_series` VALUES (450, 5204, '308S');
INSERT INTO `parkwash_car_series` VALUES (451, 5204, '4008');
INSERT INTO `parkwash_car_series` VALUES (452, 5204, '5008');
INSERT INTO `parkwash_car_series` VALUES (453, 5204, '207-两厢');
INSERT INTO `parkwash_car_series` VALUES (454, 5204, '307-三厢');
INSERT INTO `parkwash_car_series` VALUES (455, 5204, '307-两厢');
INSERT INTO `parkwash_car_series` VALUES (456, 5204, '206');
INSERT INTO `parkwash_car_series` VALUES (457, 5204, '207');
INSERT INTO `parkwash_car_series` VALUES (458, 5204, '3008');
INSERT INTO `parkwash_car_series` VALUES (459, 5204, '307');
INSERT INTO `parkwash_car_series` VALUES (460, 5204, '308');
INSERT INTO `parkwash_car_series` VALUES (461, 5204, 'RCZ');
INSERT INTO `parkwash_car_series` VALUES (462, 5204, '4008');
INSERT INTO `parkwash_car_series` VALUES (463, 5204, '4007');
INSERT INTO `parkwash_car_series` VALUES (464, 5204, '407');
INSERT INTO `parkwash_car_series` VALUES (465, 5204, '5008');
INSERT INTO `parkwash_car_series` VALUES (466, 5204, '607');
INSERT INTO `parkwash_car_series` VALUES (467, 5204, '807');
INSERT INTO `parkwash_car_series` VALUES (468, 5538, '天语SX4');
INSERT INTO `parkwash_car_series` VALUES (469, 5538, '羚羊');
INSERT INTO `parkwash_car_series` VALUES (470, 5538, '雨燕');
INSERT INTO `parkwash_car_series` VALUES (471, 5538, '奥拓');
INSERT INTO `parkwash_car_series` VALUES (472, 5538, '天语·尚悦');
INSERT INTO `parkwash_car_series` VALUES (473, 5538, '锋驭');
INSERT INTO `parkwash_car_series` VALUES (474, 5538, '启悦');
INSERT INTO `parkwash_car_series` VALUES (475, 5538, '天语SX4-两厢');
INSERT INTO `parkwash_car_series` VALUES (476, 5538, '维特拉');
INSERT INTO `parkwash_car_series` VALUES (477, 5538, '天语SX4-三厢');
INSERT INTO `parkwash_car_series` VALUES (478, 5538, '北斗星');
INSERT INTO `parkwash_car_series` VALUES (479, 5538, '北斗星 竞驭');
INSERT INTO `parkwash_car_series` VALUES (480, 5538, '北斗星e+');
INSERT INTO `parkwash_car_series` VALUES (481, 5538, '北斗星X5');
INSERT INTO `parkwash_car_series` VALUES (482, 5538, '利亚纳-两厢');
INSERT INTO `parkwash_car_series` VALUES (483, 5538, '利亚纳-三厢');
INSERT INTO `parkwash_car_series` VALUES (484, 5538, '浪迪');
INSERT INTO `parkwash_car_series` VALUES (485, 5538, '派喜');
INSERT INTO `parkwash_car_series` VALUES (486, 5538, '利亚纳A6-两厢');
INSERT INTO `parkwash_car_series` VALUES (487, 5538, '利亚纳A6-三厢');
INSERT INTO `parkwash_car_series` VALUES (488, 5538, '吉姆尼');
INSERT INTO `parkwash_car_series` VALUES (489, 5538, '超级维特拉');
INSERT INTO `parkwash_car_series` VALUES (490, 5538, '凯泽西');
INSERT INTO `parkwash_car_series` VALUES (491, 5538, '速翼特');
INSERT INTO `parkwash_car_series` VALUES (492, 5538, 'Alto 奥拓');
INSERT INTO `parkwash_car_series` VALUES (493, 5538, 'APV');
INSERT INTO `parkwash_car_series` VALUES (494, 5538, 'Splash');
INSERT INTO `parkwash_car_series` VALUES (495, 5538, 'SX4 Cross');
INSERT INTO `parkwash_car_series` VALUES (496, 5538, 'SX4-Sedan SX4-三厢');
INSERT INTO `parkwash_car_series` VALUES (497, 5538, 'XL7');
INSERT INTO `parkwash_car_series` VALUES (498, 5829, '本田CR-V');
INSERT INTO `parkwash_car_series` VALUES (499, 5829, '本田XR-V');
INSERT INTO `parkwash_car_series` VALUES (500, 5829, '思域');
INSERT INTO `parkwash_car_series` VALUES (501, 5829, '思铂睿');
INSERT INTO `parkwash_car_series` VALUES (502, 5829, '思铭');
INSERT INTO `parkwash_car_series` VALUES (503, 5829, '艾力绅');
INSERT INTO `parkwash_car_series` VALUES (504, 5829, '杰德');
INSERT INTO `parkwash_car_series` VALUES (505, 5829, '哥瑞');
INSERT INTO `parkwash_car_series` VALUES (506, 5829, '竞瑞');
INSERT INTO `parkwash_car_series` VALUES (507, 5829, '本田UR-V');
INSERT INTO `parkwash_car_series` VALUES (508, 5829, '凌派');
INSERT INTO `parkwash_car_series` VALUES (509, 5829, '奥德赛');
INSERT INTO `parkwash_car_series` VALUES (510, 5829, '思迪');
INSERT INTO `parkwash_car_series` VALUES (511, 5829, '歌诗图');
INSERT INTO `parkwash_car_series` VALUES (512, 5829, '缤智');
INSERT INTO `parkwash_car_series` VALUES (513, 5829, '锋范');
INSERT INTO `parkwash_car_series` VALUES (514, 5829, '雅阁');
INSERT INTO `parkwash_car_series` VALUES (515, 5829, '飞度');
INSERT INTO `parkwash_car_series` VALUES (516, 5829, '理念S1');
INSERT INTO `parkwash_car_series` VALUES (517, 5829, '雅阁混动');
INSERT INTO `parkwash_car_series` VALUES (518, 5829, '冠道');
INSERT INTO `parkwash_car_series` VALUES (519, 5829, 'Accord 雅阁');
INSERT INTO `parkwash_car_series` VALUES (520, 5829, 'Airwave 气浪');
INSERT INTO `parkwash_car_series` VALUES (521, 5829, 'Civic 思域');
INSERT INTO `parkwash_car_series` VALUES (522, 5829, 'CR-V');
INSERT INTO `parkwash_car_series` VALUES (523, 5829, 'CR-Z');
INSERT INTO `parkwash_car_series` VALUES (524, 5829, 'Crosstour 歌诗图');
INSERT INTO `parkwash_car_series` VALUES (525, 5829, 'Element 元素');
INSERT INTO `parkwash_car_series` VALUES (526, 5829, 'FCX');
INSERT INTO `parkwash_car_series` VALUES (527, 5829, 'Fit 飞度');
INSERT INTO `parkwash_car_series` VALUES (528, 5829, 'Insight 音赛特');
INSERT INTO `parkwash_car_series` VALUES (529, 5829, 'Legend 里程');
INSERT INTO `parkwash_car_series` VALUES (530, 5829, 'Odyssey 奥德赛');
INSERT INTO `parkwash_car_series` VALUES (531, 5829, 'Pilot 领航员');
INSERT INTO `parkwash_car_series` VALUES (532, 5829, 'Ridgeline');
INSERT INTO `parkwash_car_series` VALUES (533, 5829, 'S2000');
INSERT INTO `parkwash_car_series` VALUES (534, 5829, 'Stream 时韵');
INSERT INTO `parkwash_car_series` VALUES (535, 5963, 'K2');
INSERT INTO `parkwash_car_series` VALUES (536, 5963, 'K3');
INSERT INTO `parkwash_car_series` VALUES (537, 5963, 'K3S');
INSERT INTO `parkwash_car_series` VALUES (538, 5963, 'KX3');
INSERT INTO `parkwash_car_series` VALUES (539, 5963, 'K4');
INSERT INTO `parkwash_car_series` VALUES (540, 5963, 'K5');
INSERT INTO `parkwash_car_series` VALUES (541, 5963, '千里马');
INSERT INTO `parkwash_car_series` VALUES (542, 5963, '嘉华');
INSERT INTO `parkwash_car_series` VALUES (543, 5963, '智跑');
INSERT INTO `parkwash_car_series` VALUES (544, 5963, '狮跑');
INSERT INTO `parkwash_car_series` VALUES (545, 5963, '福瑞迪');
INSERT INTO `parkwash_car_series` VALUES (546, 5963, '秀尔');
INSERT INTO `parkwash_car_series` VALUES (547, 5963, '赛拉图');
INSERT INTO `parkwash_car_series` VALUES (548, 5963, '远舰');
INSERT INTO `parkwash_car_series` VALUES (549, 5963, 'K5 Hybrid');
INSERT INTO `parkwash_car_series` VALUES (550, 5963, '锐欧');
INSERT INTO `parkwash_car_series` VALUES (551, 5963, 'KX5');
INSERT INTO `parkwash_car_series` VALUES (552, 5963, 'KX7');
INSERT INTO `parkwash_car_series` VALUES (553, 5963, 'K2-三厢');
INSERT INTO `parkwash_car_series` VALUES (554, 5963, '赛拉图 欧风');
INSERT INTO `parkwash_car_series` VALUES (555, 5963, 'KX CROSS');
INSERT INTO `parkwash_car_series` VALUES (556, 5963, '凯绅');
INSERT INTO `parkwash_car_series` VALUES (557, 5963, '焕驰');
INSERT INTO `parkwash_car_series` VALUES (558, 5963, 'VQ 威客');
INSERT INTO `parkwash_car_series` VALUES (559, 5963, 'Sorento 索兰托');
INSERT INTO `parkwash_car_series` VALUES (560, 5963, 'VQ-R 威客-R');
INSERT INTO `parkwash_car_series` VALUES (561, 5963, 'New Carens 新佳乐');
INSERT INTO `parkwash_car_series` VALUES (562, 5963, 'Cadenza 凯尊');
INSERT INTO `parkwash_car_series` VALUES (563, 5963, 'Shuma 速迈');
INSERT INTO `parkwash_car_series` VALUES (564, 5963, 'Borrego 霸锐');
INSERT INTO `parkwash_car_series` VALUES (565, 5963, 'K5 Hybrid');
INSERT INTO `parkwash_car_series` VALUES (566, 5963, 'Sorento L 索兰托L');
INSERT INTO `parkwash_car_series` VALUES (567, 5963, 'K9');
INSERT INTO `parkwash_car_series` VALUES (568, 5963, 'Carnival 嘉华');
INSERT INTO `parkwash_car_series` VALUES (569, 5963, 'cee’d');
INSERT INTO `parkwash_car_series` VALUES (570, 5963, 'cee’d 旅行车');
INSERT INTO `parkwash_car_series` VALUES (571, 5963, 'Cerato-Hatchback 赛拉图-两厢');
INSERT INTO `parkwash_car_series` VALUES (572, 5963, 'Cerato-Sedan 赛拉图-三厢');
INSERT INTO `parkwash_car_series` VALUES (573, 5963, 'Forte Koup 福瑞迪 Koup');
INSERT INTO `parkwash_car_series` VALUES (574, 5963, 'Forte 福瑞迪');
INSERT INTO `parkwash_car_series` VALUES (575, 5963, 'Imperial 帝国');
INSERT INTO `parkwash_car_series` VALUES (576, 5963, 'Niro 极睿');
INSERT INTO `parkwash_car_series` VALUES (577, 5963, 'Opirus 欧菲莱斯');
INSERT INTO `parkwash_car_series` VALUES (578, 5963, 'Optima 欧迪玛');
INSERT INTO `parkwash_car_series` VALUES (579, 5963, 'Picanto');
INSERT INTO `parkwash_car_series` VALUES (580, 5963, 'Rio-Hatchback 丽欧-两厢');
INSERT INTO `parkwash_car_series` VALUES (581, 5963, 'Rio-Sedan 丽欧-三厢');
INSERT INTO `parkwash_car_series` VALUES (582, 5963, 'Soul 秀尔');
INSERT INTO `parkwash_car_series` VALUES (583, 5963, 'Sportage 狮跑');
INSERT INTO `parkwash_car_series` VALUES (584, 6280, '3系');
INSERT INTO `parkwash_car_series` VALUES (585, 6280, '5系');
INSERT INTO `parkwash_car_series` VALUES (586, 6280, 'X1');
INSERT INTO `parkwash_car_series` VALUES (587, 6280, '2系');
INSERT INTO `parkwash_car_series` VALUES (588, 6280, '1系');
INSERT INTO `parkwash_car_series` VALUES (589, 6280, '5系');
INSERT INTO `parkwash_car_series` VALUES (590, 6280, '1系');
INSERT INTO `parkwash_car_series` VALUES (591, 6280, '3系');
INSERT INTO `parkwash_car_series` VALUES (592, 6280, '7系');
INSERT INTO `parkwash_car_series` VALUES (593, 6280, 'X3');
INSERT INTO `parkwash_car_series` VALUES (594, 6280, 'X5');
INSERT INTO `parkwash_car_series` VALUES (595, 6280, '1系M');
INSERT INTO `parkwash_car_series` VALUES (596, 6280, '4系敞篷');
INSERT INTO `parkwash_car_series` VALUES (597, 6280, '4系双门');
INSERT INTO `parkwash_car_series` VALUES (598, 6280, '4系四门');
INSERT INTO `parkwash_car_series` VALUES (599, 6280, '5系GT');
INSERT INTO `parkwash_car_series` VALUES (600, 6280, '6系');
INSERT INTO `parkwash_car_series` VALUES (601, 6280, 'X6');
INSERT INTO `parkwash_car_series` VALUES (602, 6280, 'M3');
INSERT INTO `parkwash_car_series` VALUES (603, 6280, 'M5');
INSERT INTO `parkwash_car_series` VALUES (604, 6280, 'M6');
INSERT INTO `parkwash_car_series` VALUES (605, 6280, 'X5 M');
INSERT INTO `parkwash_car_series` VALUES (606, 6280, 'X6 M');
INSERT INTO `parkwash_car_series` VALUES (607, 6280, 'Z4');
INSERT INTO `parkwash_car_series` VALUES (608, 6280, '2系');
INSERT INTO `parkwash_car_series` VALUES (609, 6280, 'X1');
INSERT INTO `parkwash_car_series` VALUES (610, 6280, '3系GT');
INSERT INTO `parkwash_car_series` VALUES (611, 6280, 'X4');
INSERT INTO `parkwash_car_series` VALUES (612, 6280, 'M2');
INSERT INTO `parkwash_car_series` VALUES (613, 6280, 'M4');
INSERT INTO `parkwash_car_series` VALUES (614, 6280, 'i3');
INSERT INTO `parkwash_car_series` VALUES (615, 6280, 'i8');
INSERT INTO `parkwash_car_series` VALUES (616, 6792, '雪铁龙C3-XR');
INSERT INTO `parkwash_car_series` VALUES (617, 6792, '雪铁龙C4L');
INSERT INTO `parkwash_car_series` VALUES (618, 6792, '雪铁龙C5');
INSERT INTO `parkwash_car_series` VALUES (619, 6792, '凯旋');
INSERT INTO `parkwash_car_series` VALUES (620, 6792, '雪铁龙C2');
INSERT INTO `parkwash_car_series` VALUES (621, 6792, '爱丽舍');
INSERT INTO `parkwash_car_series` VALUES (622, 6792, '世嘉');
INSERT INTO `parkwash_car_series` VALUES (623, 6792, '富康');
INSERT INTO `parkwash_car_series` VALUES (624, 6792, 'C4世嘉');
INSERT INTO `parkwash_car_series` VALUES (625, 6792, '雪铁龙C6');
INSERT INTO `parkwash_car_series` VALUES (626, 6792, '毕加索');
INSERT INTO `parkwash_car_series` VALUES (627, 6792, 'C4毕加索(进口)');
INSERT INTO `parkwash_car_series` VALUES (628, 6792, 'C4 Aircross');
INSERT INTO `parkwash_car_series` VALUES (629, 6792, 'C4');
INSERT INTO `parkwash_car_series` VALUES (630, 6792, 'Grand C4 Picasso 大C4毕加索');
INSERT INTO `parkwash_car_series` VALUES (631, 6792, 'C-Crosser');
INSERT INTO `parkwash_car_series` VALUES (632, 6792, 'C1');
INSERT INTO `parkwash_car_series` VALUES (633, 6792, 'C2');
INSERT INTO `parkwash_car_series` VALUES (634, 6792, 'C3');
INSERT INTO `parkwash_car_series` VALUES (635, 6792, 'C4 Coupe');
INSERT INTO `parkwash_car_series` VALUES (636, 6792, 'C5');
INSERT INTO `parkwash_car_series` VALUES (637, 6792, 'C5 Wagon （C5 旅行车）');
INSERT INTO `parkwash_car_series` VALUES (638, 6792, 'C6');
INSERT INTO `parkwash_car_series` VALUES (639, 6792, 'C8');
INSERT INTO `parkwash_car_series` VALUES (640, 6792, 'C3 Picasso （C3 毕加索）');
INSERT INTO `parkwash_car_series` VALUES (641, 6792, 'C4 Picasso （C4 毕加索）');
INSERT INTO `parkwash_car_series` VALUES (642, 7747, '神行者2');
INSERT INTO `parkwash_car_series` VALUES (643, 7747, '发现3');
INSERT INTO `parkwash_car_series` VALUES (644, 7747, '第四代发现');
INSERT INTO `parkwash_car_series` VALUES (645, 7747, '发现神行');
INSERT INTO `parkwash_car_series` VALUES (646, 7747, '揽胜');
INSERT INTO `parkwash_car_series` VALUES (647, 7747, '揽胜运动版');
INSERT INTO `parkwash_car_series` VALUES (648, 7747, '揽胜极光');
INSERT INTO `parkwash_car_series` VALUES (649, 7747, '揽胜极光（国产）');
INSERT INTO `parkwash_car_series` VALUES (650, 7747, '发现神行（国产）');
INSERT INTO `parkwash_car_series` VALUES (651, 7747, '卫士');
INSERT INTO `parkwash_car_series` VALUES (652, 7747, '发现');
INSERT INTO `parkwash_car_series` VALUES (653, 7747, '神行者');
INSERT INTO `parkwash_car_series` VALUES (654, 7941, '捷豹XF');
INSERT INTO `parkwash_car_series` VALUES (655, 7941, '捷豹XJ');
INSERT INTO `parkwash_car_series` VALUES (656, 7941, '捷豹F-TYPE');
INSERT INTO `parkwash_car_series` VALUES (657, 7941, '捷豹XK');
INSERT INTO `parkwash_car_series` VALUES (658, 7941, '捷豹XE R-SPORT');
INSERT INTO `parkwash_car_series` VALUES (659, 7941, '捷豹XE S');
INSERT INTO `parkwash_car_series` VALUES (660, 7941, '捷豹F-PACE');
INSERT INTO `parkwash_car_series` VALUES (661, 7941, '捷豹XFL（国产）');
INSERT INTO `parkwash_car_series` VALUES (662, 7941, '捷豹S-Type');
INSERT INTO `parkwash_car_series` VALUES (663, 8806, '昂科威');
INSERT INTO `parkwash_car_series` VALUES (664, 8806, '昂科拉');
INSERT INTO `parkwash_car_series` VALUES (665, 8806, 'GL8商务');
INSERT INTO `parkwash_car_series` VALUES (666, 8806, '君越');
INSERT INTO `parkwash_car_series` VALUES (667, 8806, '君威');
INSERT INTO `parkwash_car_series` VALUES (668, 8806, '英朗');
INSERT INTO `parkwash_car_series` VALUES (669, 8806, '林荫大道');
INSERT INTO `parkwash_car_series` VALUES (670, 8806, '凯越');
INSERT INTO `parkwash_car_series` VALUES (671, 8806, '昂科雷');
INSERT INTO `parkwash_car_series` VALUES (672, 8806, '威朗');
INSERT INTO `parkwash_car_series` VALUES (673, 8806, 'VELITE 5');
INSERT INTO `parkwash_car_series` VALUES (674, 8806, '君越');
INSERT INTO `parkwash_car_series` VALUES (675, 9194, '荣威950');
INSERT INTO `parkwash_car_series` VALUES (676, 9194, '荣威750');
INSERT INTO `parkwash_car_series` VALUES (677, 9194, '荣威W5');
INSERT INTO `parkwash_car_series` VALUES (678, 9194, '荣威550');
INSERT INTO `parkwash_car_series` VALUES (679, 9194, '荣威350');
INSERT INTO `parkwash_car_series` VALUES (680, 9194, '荣威360');
INSERT INTO `parkwash_car_series` VALUES (681, 9194, '荣威RX5');
INSERT INTO `parkwash_car_series` VALUES (682, 9194, '荣威e550');
INSERT INTO `parkwash_car_series` VALUES (683, 9194, '荣威e950');
INSERT INTO `parkwash_car_series` VALUES (684, 9194, '荣威E50');
INSERT INTO `parkwash_car_series` VALUES (685, 9194, '荣威ei6');
INSERT INTO `parkwash_car_series` VALUES (686, 9194, '荣威i6');
INSERT INTO `parkwash_car_series` VALUES (687, 9194, '荣威eRX5');
INSERT INTO `parkwash_car_series` VALUES (688, 9350, '乐风');
INSERT INTO `parkwash_car_series` VALUES (689, 9350, '科鲁兹');
INSERT INTO `parkwash_car_series` VALUES (690, 9350, '赛欧');
INSERT INTO `parkwash_car_series` VALUES (691, 9350, '迈锐宝');
INSERT INTO `parkwash_car_series` VALUES (692, 9350, '科鲁兹掀背');
INSERT INTO `parkwash_car_series` VALUES (693, 9350, '科帕奇');
INSERT INTO `parkwash_car_series` VALUES (694, 9350, '乐骋');
INSERT INTO `parkwash_car_series` VALUES (695, 9350, '景程');
INSERT INTO `parkwash_car_series` VALUES (696, 9350, '爱唯欧');
INSERT INTO `parkwash_car_series` VALUES (697, 9350, '创酷');
INSERT INTO `parkwash_car_series` VALUES (698, 9350, '乐风RV');
INSERT INTO `parkwash_car_series` VALUES (699, 9350, '赛欧3');
INSERT INTO `parkwash_car_series` VALUES (700, 9350, '迈锐宝XL');
INSERT INTO `parkwash_car_series` VALUES (701, 9350, '科鲁兹经典');
INSERT INTO `parkwash_car_series` VALUES (702, 9350, '科沃兹');
INSERT INTO `parkwash_car_series` VALUES (703, 9350, '科鲁兹-两厢');
INSERT INTO `parkwash_car_series` VALUES (704, 9350, '探界者');
INSERT INTO `parkwash_car_series` VALUES (705, 9350, '乐驰');
INSERT INTO `parkwash_car_series` VALUES (706, 9350, '赛欧-三厢');
INSERT INTO `parkwash_car_series` VALUES (707, 9350, '赛欧-两厢');
INSERT INTO `parkwash_car_series` VALUES (708, 9350, '斯帕可SPARK');
INSERT INTO `parkwash_car_series` VALUES (709, 9350, '科迈罗');
INSERT INTO `parkwash_car_series` VALUES (710, 9350, '科迈罗 RS');
INSERT INTO `parkwash_car_series` VALUES (711, 9350, 'Avalanche 雪崩');
INSERT INTO `parkwash_car_series` VALUES (712, 9350, 'Aveo-Hatchback 乐骋-两厢');
INSERT INTO `parkwash_car_series` VALUES (713, 9350, 'Aveo-Sedan 乐骋-三厢');
INSERT INTO `parkwash_car_series` VALUES (714, 9350, 'Camaro Convertible 科迈罗 敞篷');
INSERT INTO `parkwash_car_series` VALUES (715, 9350, 'Camaro RS 科迈罗 RS');
INSERT INTO `parkwash_car_series` VALUES (716, 9350, 'Camaro 科迈罗');
INSERT INTO `parkwash_car_series` VALUES (717, 9350, 'Camaro-SS 科迈罗-SS');
INSERT INTO `parkwash_car_series` VALUES (718, 9350, 'Captiva 科帕奇');
INSERT INTO `parkwash_car_series` VALUES (719, 9350, 'Cobalt Coupe 科宝 Coupe');
INSERT INTO `parkwash_car_series` VALUES (720, 9350, 'Cobalt 科宝');
INSERT INTO `parkwash_car_series` VALUES (721, 9350, 'Cobalt-SS 科宝-SS');
INSERT INTO `parkwash_car_series` VALUES (722, 9350, 'Colorado 库罗德');
INSERT INTO `parkwash_car_series` VALUES (723, 9350, 'Corvette C6 Convertible 克尔维特C6 敞篷');
INSERT INTO `parkwash_car_series` VALUES (724, 9350, 'Corvette C6 Coupe 克尔维特C6 Coupe');
INSERT INTO `parkwash_car_series` VALUES (725, 9350, 'Corvette Convertible 克尔维特 敞篷');
INSERT INTO `parkwash_car_series` VALUES (726, 9350, 'Corvette Z06 Carbon 克尔维特Z06 Carbon');
INSERT INTO `parkwash_car_series` VALUES (727, 9350, 'Corvette Z06 克尔维特Z06');
INSERT INTO `parkwash_car_series` VALUES (728, 9350, 'Corvette ZR1 克尔维特ZR1');
INSERT INTO `parkwash_car_series` VALUES (729, 9350, 'Cruze 科鲁兹');
INSERT INTO `parkwash_car_series` VALUES (730, 9350, 'Epica 景程');
INSERT INTO `parkwash_car_series` VALUES (731, 9350, 'Equinox 春分');
INSERT INTO `parkwash_car_series` VALUES (732, 9350, 'Express');
INSERT INTO `parkwash_car_series` VALUES (733, 9350, 'Express1500');
INSERT INTO `parkwash_car_series` VALUES (734, 9350, 'Express2500');
INSERT INTO `parkwash_car_series` VALUES (735, 9350, 'Express3500');
INSERT INTO `parkwash_car_series` VALUES (736, 9350, 'HHR');
INSERT INTO `parkwash_car_series` VALUES (737, 9350, 'Malibu 迈锐宝');
INSERT INTO `parkwash_car_series` VALUES (738, 9350, 'Silverado 索罗德');
INSERT INTO `parkwash_car_series` VALUES (739, 9350, 'Spark 斯帕可');
INSERT INTO `parkwash_car_series` VALUES (740, 9350, 'Suburban');
INSERT INTO `parkwash_car_series` VALUES (741, 9350, 'Tahoe Hybrid 豪放 油电混合');
INSERT INTO `parkwash_car_series` VALUES (742, 9350, 'Tahoe 豪放');
INSERT INTO `parkwash_car_series` VALUES (743, 9350, 'Trailblazer 全能先锋');
INSERT INTO `parkwash_car_series` VALUES (744, 9350, 'Traverse');
INSERT INTO `parkwash_car_series` VALUES (745, 9350, 'Volt 沃蓝达');
INSERT INTO `parkwash_car_series` VALUES (746, 9681, 'MG6');
INSERT INTO `parkwash_car_series` VALUES (747, 9681, 'MG3');
INSERT INTO `parkwash_car_series` VALUES (748, 9681, 'MG5');
INSERT INTO `parkwash_car_series` VALUES (749, 9681, 'MG7');
INSERT INTO `parkwash_car_series` VALUES (750, 9681, 'MG3 SW');
INSERT INTO `parkwash_car_series` VALUES (751, 9681, '锐腾');
INSERT INTO `parkwash_car_series` VALUES (752, 9681, '锐行');
INSERT INTO `parkwash_car_series` VALUES (753, 9681, 'MG ZS');
INSERT INTO `parkwash_car_series` VALUES (754, 9681, 'MG TF');
INSERT INTO `parkwash_car_series` VALUES (755, 9996, '科雷傲');
INSERT INTO `parkwash_car_series` VALUES (756, 9996, '风朗');
INSERT INTO `parkwash_car_series` VALUES (757, 9996, '纬度');
INSERT INTO `parkwash_car_series` VALUES (758, 9996, '卡缤');
INSERT INTO `parkwash_car_series` VALUES (759, 9996, '塔利斯曼');
INSERT INTO `parkwash_car_series` VALUES (760, 9996, '风景');
INSERT INTO `parkwash_car_series` VALUES (761, 9996, 'Clio 旅行车');
INSERT INTO `parkwash_car_series` VALUES (762, 9996, 'Clio');
INSERT INTO `parkwash_car_series` VALUES (763, 9996, 'Espace');
INSERT INTO `parkwash_car_series` VALUES (764, 9996, 'Grand Espace');
INSERT INTO `parkwash_car_series` VALUES (765, 9996, 'Kangoo');
INSERT INTO `parkwash_car_series` VALUES (766, 9996, '拉古娜');
INSERT INTO `parkwash_car_series` VALUES (767, 9996, '梅甘娜 CC');
INSERT INTO `parkwash_car_series` VALUES (768, 9996, '梅甘娜');
INSERT INTO `parkwash_car_series` VALUES (769, 9996, 'Megane R.S.');
INSERT INTO `parkwash_car_series` VALUES (770, 9996, 'Grand Modus');
INSERT INTO `parkwash_car_series` VALUES (771, 9996, 'Modus');
INSERT INTO `parkwash_car_series` VALUES (772, 9996, 'Twingo');
INSERT INTO `parkwash_car_series` VALUES (773, 9996, '科雷嘉');
INSERT INTO `parkwash_car_series` VALUES (774, 9996, '科雷傲（国产）');
INSERT INTO `parkwash_car_series` VALUES (775, 10092, 'Caliber 酷博');
INSERT INTO `parkwash_car_series` VALUES (776, 10092, 'Journey 酷威');
INSERT INTO `parkwash_car_series` VALUES (777, 10092, '凯领');
INSERT INTO `parkwash_car_series` VALUES (778, 10092, 'Avenger 锋哲');
INSERT INTO `parkwash_car_series` VALUES (779, 10092, 'Challenger 挑战者');
INSERT INTO `parkwash_car_series` VALUES (780, 10092, 'Charger');
INSERT INTO `parkwash_car_series` VALUES (781, 10092, 'Magnum');
INSERT INTO `parkwash_car_series` VALUES (782, 10092, 'Nitro 翼龙');
INSERT INTO `parkwash_car_series` VALUES (783, 10092, 'Ram 公羊');
INSERT INTO `parkwash_car_series` VALUES (784, 10092, '蝰蛇');
INSERT INTO `parkwash_car_series` VALUES (785, 10109, '300C');
INSERT INTO `parkwash_car_series` VALUES (786, 10109, 'Grand Voyager 大捷龙');
INSERT INTO `parkwash_car_series` VALUES (787, 10109, '300S');
INSERT INTO `parkwash_car_series` VALUES (788, 10109, '铂锐');
INSERT INTO `parkwash_car_series` VALUES (789, 10109, '大捷龙');
INSERT INTO `parkwash_car_series` VALUES (790, 10109, '200');
INSERT INTO `parkwash_car_series` VALUES (791, 10109, 'Aspen');
INSERT INTO `parkwash_car_series` VALUES (792, 10109, 'Crossfire 交叉火力');
INSERT INTO `parkwash_car_series` VALUES (793, 10109, 'PT Cruiser PT漫步者');
INSERT INTO `parkwash_car_series` VALUES (794, 10109, 'Sebring 赛百灵');
INSERT INTO `parkwash_car_series` VALUES (795, 10132, 'Commander 指挥官');
INSERT INTO `parkwash_car_series` VALUES (796, 10132, 'Compass 指南者');
INSERT INTO `parkwash_car_series` VALUES (797, 10132, 'Grand Cherokee 大切诺基');
INSERT INTO `parkwash_car_series` VALUES (798, 10132, 'Patriot 自由客');
INSERT INTO `parkwash_car_series` VALUES (799, 10132, 'Wrangler 牧马人');
INSERT INTO `parkwash_car_series` VALUES (800, 10132, 'Cherokee 自由光');
INSERT INTO `parkwash_car_series` VALUES (801, 10132, '大切诺基 征程4000');
INSERT INTO `parkwash_car_series` VALUES (802, 10132, '大切诺基 征途4700');
INSERT INTO `parkwash_car_series` VALUES (803, 10132, '大切诺基 4700');
INSERT INTO `parkwash_car_series` VALUES (804, 10132, 'Liberty 自由人');
INSERT INTO `parkwash_car_series` VALUES (805, 10132, '自由光');
INSERT INTO `parkwash_car_series` VALUES (806, 10132, '自由侠');
INSERT INTO `parkwash_car_series` VALUES (807, 10132, '指南者');
INSERT INTO `parkwash_car_series` VALUES (808, 10383, 'DS3');
INSERT INTO `parkwash_car_series` VALUES (809, 10383, 'DS3 Cabrio');
INSERT INTO `parkwash_car_series` VALUES (810, 10383, 'DS4');
INSERT INTO `parkwash_car_series` VALUES (811, 10383, 'DS5');
INSERT INTO `parkwash_car_series` VALUES (812, 10383, 'DS 5LS');
INSERT INTO `parkwash_car_series` VALUES (813, 10383, 'DS6');
INSERT INTO `parkwash_car_series` VALUES (814, 10383, 'DS 4S');
INSERT INTO `parkwash_car_series` VALUES (815, 10434, 'Bravo （博悦）');
INSERT INTO `parkwash_car_series` VALUES (816, 10434, 'Linea （领雅）');
INSERT INTO `parkwash_car_series` VALUES (817, 10434, 'Freemont （菲跃）');
INSERT INTO `parkwash_car_series` VALUES (818, 10434, '500C');
INSERT INTO `parkwash_car_series` VALUES (819, 10434, '500');
INSERT INTO `parkwash_car_series` VALUES (820, 10434, '500 Abarth');
INSERT INTO `parkwash_car_series` VALUES (821, 10434, 'Doblo（多宝）');
INSERT INTO `parkwash_car_series` VALUES (822, 10434, 'Linea（领雅）');
INSERT INTO `parkwash_car_series` VALUES (823, 10434, 'Panda（熊猫）');
INSERT INTO `parkwash_car_series` VALUES (824, 10434, 'Grande Punto（朋多）');
INSERT INTO `parkwash_car_series` VALUES (825, 10434, 'Qubo');
INSERT INTO `parkwash_car_series` VALUES (826, 10434, 'Sedici');
INSERT INTO `parkwash_car_series` VALUES (827, 10434, 'Stilo Wagon（时尚 旅行版）');
INSERT INTO `parkwash_car_series` VALUES (828, 10434, 'Stilo（时尚）');
INSERT INTO `parkwash_car_series` VALUES (829, 10434, '菲翔');
INSERT INTO `parkwash_car_series` VALUES (830, 10434, '致悦');
INSERT INTO `parkwash_car_series` VALUES (831, 10434, '周末风');
INSERT INTO `parkwash_car_series` VALUES (832, 10434, '派力奥');
INSERT INTO `parkwash_car_series` VALUES (833, 10434, '派朗');
INSERT INTO `parkwash_car_series` VALUES (834, 10434, '西耶那');
INSERT INTO `parkwash_car_series` VALUES (835, 10502, 'CTS');
INSERT INTO `parkwash_car_series` VALUES (836, 10502, 'CTS-V');
INSERT INTO `parkwash_car_series` VALUES (837, 10502, 'Escalade 凯雷德');
INSERT INTO `parkwash_car_series` VALUES (838, 10502, 'Escalade Hybrid 凯雷德 油电混合');
INSERT INTO `parkwash_car_series` VALUES (839, 10502, 'Escalade EXT 凯雷德 EXT');
INSERT INTO `parkwash_car_series` VALUES (840, 10502, 'SRX');
INSERT INTO `parkwash_car_series` VALUES (841, 10502, 'CTS Coupe');
INSERT INTO `parkwash_car_series` VALUES (842, 10502, 'CTS-V Coupe');
INSERT INTO `parkwash_car_series` VALUES (843, 10502, 'Escalade ESV 凯雷德 ESV');
INSERT INTO `parkwash_car_series` VALUES (844, 10502, 'ATS');
INSERT INTO `parkwash_car_series` VALUES (845, 10502, 'BLS');
INSERT INTO `parkwash_car_series` VALUES (846, 10502, 'BLS Wagon');
INSERT INTO `parkwash_car_series` VALUES (847, 10502, 'CTS Sport Wagon');
INSERT INTO `parkwash_car_series` VALUES (848, 10502, 'CTS-V Wagon');
INSERT INTO `parkwash_car_series` VALUES (849, 10502, 'DTS');
INSERT INTO `parkwash_car_series` VALUES (850, 10502, 'STS');
INSERT INTO `parkwash_car_series` VALUES (851, 10502, 'STS-V');
INSERT INTO `parkwash_car_series` VALUES (852, 10502, 'XLR');
INSERT INTO `parkwash_car_series` VALUES (853, 10502, 'CTS');
INSERT INTO `parkwash_car_series` VALUES (854, 10502, '赛威SLS');
INSERT INTO `parkwash_car_series` VALUES (855, 10502, 'XTS');
INSERT INTO `parkwash_car_series` VALUES (856, 10502, 'ATS-L');
INSERT INTO `parkwash_car_series` VALUES (857, 10502, 'CT6');
INSERT INTO `parkwash_car_series` VALUES (858, 10502, 'XT5');
INSERT INTO `parkwash_car_series` VALUES (859, 10690, '保时捷 911');
INSERT INTO `parkwash_car_series` VALUES (860, 10690, 'Boxster 博克斯特');
INSERT INTO `parkwash_car_series` VALUES (861, 10690, 'Cayenne 卡宴');
INSERT INTO `parkwash_car_series` VALUES (862, 10690, 'Cayman 卡曼');
INSERT INTO `parkwash_car_series` VALUES (863, 10690, 'Panamera 帕纳美拉');
INSERT INTO `parkwash_car_series` VALUES (864, 10690, '保时捷 918');
INSERT INTO `parkwash_car_series` VALUES (865, 10690, 'Macan');
INSERT INTO `parkwash_car_series` VALUES (866, 10691, 'B50');
INSERT INTO `parkwash_car_series` VALUES (867, 10691, 'B70');
INSERT INTO `parkwash_car_series` VALUES (868, 10691, 'B90');
INSERT INTO `parkwash_car_series` VALUES (869, 10691, 'X80');
INSERT INTO `parkwash_car_series` VALUES (870, 10691, 'B30');
INSERT INTO `parkwash_car_series` VALUES (871, 10691, 'X40');
INSERT INTO `parkwash_car_series` VALUES (872, 10692, 'C30');
INSERT INTO `parkwash_car_series` VALUES (873, 10692, 'V80');
INSERT INTO `parkwash_car_series` VALUES (874, 10692, 'C50');
INSERT INTO `parkwash_car_series` VALUES (875, 10692, '风骏5');
INSERT INTO `parkwash_car_series` VALUES (876, 10692, 'M2');
INSERT INTO `parkwash_car_series` VALUES (877, 10692, 'M4');
INSERT INTO `parkwash_car_series` VALUES (878, 10692, 'C20R');
INSERT INTO `parkwash_car_series` VALUES (879, 10692, '凌傲');
INSERT INTO `parkwash_car_series` VALUES (880, 10692, '哈弗');
INSERT INTO `parkwash_car_series` VALUES (881, 10692, '哈弗B3');
INSERT INTO `parkwash_car_series` VALUES (882, 10692, '哈弗H3');
INSERT INTO `parkwash_car_series` VALUES (883, 10692, '哈弗H5');
INSERT INTO `parkwash_car_series` VALUES (884, 10692, '哈弗H6');
INSERT INTO `parkwash_car_series` VALUES (885, 10692, '哈弗M1');
INSERT INTO `parkwash_car_series` VALUES (886, 10692, '哈弗M2');
INSERT INTO `parkwash_car_series` VALUES (887, 10692, '哈弗M4');
INSERT INTO `parkwash_car_series` VALUES (888, 10692, '哈弗派');
INSERT INTO `parkwash_car_series` VALUES (889, 10692, '嘉誉');
INSERT INTO `parkwash_car_series` VALUES (890, 10692, '炫丽');
INSERT INTO `parkwash_car_series` VALUES (891, 10692, '炫丽Cross');
INSERT INTO `parkwash_car_series` VALUES (892, 10692, '赛弗');
INSERT INTO `parkwash_car_series` VALUES (893, 10692, '赛弗F1');
INSERT INTO `parkwash_car_series` VALUES (894, 10692, '赛影');
INSERT INTO `parkwash_car_series` VALUES (895, 10692, '赛铃');
INSERT INTO `parkwash_car_series` VALUES (896, 10692, '酷熊');
INSERT INTO `parkwash_car_series` VALUES (897, 10692, '金迪尔');
INSERT INTO `parkwash_car_series` VALUES (898, 10692, '长城精灵');
INSERT INTO `parkwash_car_series` VALUES (899, 10692, '长城精灵Cross');
INSERT INTO `parkwash_car_series` VALUES (900, 10692, '风骏3');
INSERT INTO `parkwash_car_series` VALUES (901, 10692, '风骏6');
INSERT INTO `parkwash_car_series` VALUES (902, 10692, '风骏房车');
INSERT INTO `parkwash_car_series` VALUES (903, 10693, 'V80');
INSERT INTO `parkwash_car_series` VALUES (904, 10693, 'G10');
INSERT INTO `parkwash_car_series` VALUES (905, 10693, 'T60');
INSERT INTO `parkwash_car_series` VALUES (906, 10693, 'EV80');
INSERT INTO `parkwash_car_series` VALUES (907, 10694, 'Outlander 欧蓝德');
INSERT INTO `parkwash_car_series` VALUES (908, 10694, 'Outlander EX 欧蓝德EX劲界');
INSERT INTO `parkwash_car_series` VALUES (909, 10694, 'Pajero 帕杰罗');
INSERT INTO `parkwash_car_series` VALUES (910, 10694, 'ASX 劲炫');
INSERT INTO `parkwash_car_series` VALUES (911, 10694, 'Pajero Sport 帕杰罗·劲畅');
INSERT INTO `parkwash_car_series` VALUES (912, 10694, 'Eclipse Spyder 伊柯丽斯 敞篷');
INSERT INTO `parkwash_car_series` VALUES (913, 10694, 'Eclipse 伊柯丽斯');
INSERT INTO `parkwash_car_series` VALUES (914, 10694, 'Endeavor');
INSERT INTO `parkwash_car_series` VALUES (915, 10694, 'Galant 戈蓝');
INSERT INTO `parkwash_car_series` VALUES (916, 10694, 'Grandis 格蓝迪');
INSERT INTO `parkwash_car_series` VALUES (917, 10694, 'Lancer Evolution 蓝瑟 翼豪陆神');
INSERT INTO `parkwash_car_series` VALUES (918, 10694, 'Lancer ES 蓝瑟 ES');
INSERT INTO `parkwash_car_series` VALUES (919, 10694, 'Lancer EX 蓝瑟 EX');
INSERT INTO `parkwash_car_series` VALUES (920, 10694, 'Lancer GTS 蓝瑟 GTS');
INSERT INTO `parkwash_car_series` VALUES (921, 10694, 'ASX 劲炫');
INSERT INTO `parkwash_car_series` VALUES (922, 10694, '帕杰罗·劲畅');
INSERT INTO `parkwash_car_series` VALUES (923, 10694, '欧蓝德');
INSERT INTO `parkwash_car_series` VALUES (924, 10694, 'ASX劲炫');
INSERT INTO `parkwash_car_series` VALUES (925, 10694, '蓝瑟');
INSERT INTO `parkwash_car_series` VALUES (926, 10694, '戈蓝');
INSERT INTO `parkwash_car_series` VALUES (927, 10694, '君阁');
INSERT INTO `parkwash_car_series` VALUES (928, 10694, '翼神');
INSERT INTO `parkwash_car_series` VALUES (929, 10694, '风迪思');
INSERT INTO `parkwash_car_series` VALUES (930, 10697, '五菱之光');
INSERT INTO `parkwash_car_series` VALUES (931, 10697, '鸿途');
INSERT INTO `parkwash_car_series` VALUES (932, 10697, 'PN系列货车');
INSERT INTO `parkwash_car_series` VALUES (933, 10697, '荣光');
INSERT INTO `parkwash_car_series` VALUES (934, 10697, '宏光');
INSERT INTO `parkwash_car_series` VALUES (935, 10697, '荣光S');
INSERT INTO `parkwash_car_series` VALUES (936, 10697, '宏光S');
INSERT INTO `parkwash_car_series` VALUES (937, 10697, '征程');
INSERT INTO `parkwash_car_series` VALUES (938, 10697, '宏光V');
INSERT INTO `parkwash_car_series` VALUES (939, 10697, '五菱之光S');
INSERT INTO `parkwash_car_series` VALUES (940, 10697, '宏光S1');
INSERT INTO `parkwash_car_series` VALUES (941, 10697, '五菱之光V');
INSERT INTO `parkwash_car_series` VALUES (942, 10697, '兴旺');
INSERT INTO `parkwash_car_series` VALUES (943, 10697, '小旋风');
INSERT INTO `parkwash_car_series` VALUES (944, 10697, '扬光');
INSERT INTO `parkwash_car_series` VALUES (945, 10697, '荣光V');
INSERT INTO `parkwash_car_series` VALUES (946, 10698, 'G25-Sedan  G25-三厢');
INSERT INTO `parkwash_car_series` VALUES (947, 10698, 'G37-Sedan  G37-三厢');
INSERT INTO `parkwash_car_series` VALUES (948, 10698, 'G37 Coupe');
INSERT INTO `parkwash_car_series` VALUES (949, 10698, 'G37 Convertible  G37 敞篷');
INSERT INTO `parkwash_car_series` VALUES (950, 10698, 'QX56');
INSERT INTO `parkwash_car_series` VALUES (951, 10698, 'FX37');
INSERT INTO `parkwash_car_series` VALUES (952, 10698, 'FX50');
INSERT INTO `parkwash_car_series` VALUES (953, 10698, 'EX25');
INSERT INTO `parkwash_car_series` VALUES (954, 10698, 'EX37');
INSERT INTO `parkwash_car_series` VALUES (955, 10698, 'JX35');
INSERT INTO `parkwash_car_series` VALUES (956, 10698, 'Q70L');
INSERT INTO `parkwash_car_series` VALUES (957, 10698, 'Q70L Hybrid  Q70L 油电混合');
INSERT INTO `parkwash_car_series` VALUES (958, 10698, 'QX50');
INSERT INTO `parkwash_car_series` VALUES (959, 10698, 'QX60');
INSERT INTO `parkwash_car_series` VALUES (960, 10698, 'QX70');
INSERT INTO `parkwash_car_series` VALUES (961, 10698, 'Q60');
INSERT INTO `parkwash_car_series` VALUES (962, 10698, 'Q60S');
INSERT INTO `parkwash_car_series` VALUES (963, 10698, 'QX80');
INSERT INTO `parkwash_car_series` VALUES (964, 10698, 'Q50');
INSERT INTO `parkwash_car_series` VALUES (965, 10698, 'Q50 Hybrid');
INSERT INTO `parkwash_car_series` VALUES (966, 10698, 'QX60 Hybrid  QX60 油电混合');
INSERT INTO `parkwash_car_series` VALUES (967, 10698, 'ESQ');
INSERT INTO `parkwash_car_series` VALUES (968, 10698, 'EX35');
INSERT INTO `parkwash_car_series` VALUES (969, 10698, 'FX35');
INSERT INTO `parkwash_car_series` VALUES (970, 10698, 'FX45');
INSERT INTO `parkwash_car_series` VALUES (971, 10698, 'G35-Sedan  G35-三厢');
INSERT INTO `parkwash_car_series` VALUES (972, 10698, 'M35');
INSERT INTO `parkwash_car_series` VALUES (973, 10698, 'M37');
INSERT INTO `parkwash_car_series` VALUES (974, 10698, 'M25');
INSERT INTO `parkwash_car_series` VALUES (975, 10698, 'M25L');
INSERT INTO `parkwash_car_series` VALUES (976, 10698, 'M35hL');
INSERT INTO `parkwash_car_series` VALUES (977, 10698, 'Q50L');
INSERT INTO `parkwash_car_series` VALUES (978, 10698, 'EX30');
INSERT INTO `parkwash_car_series` VALUES (979, 10698, 'FX30');
INSERT INTO `parkwash_car_series` VALUES (980, 10698, 'G25 Coupe');
INSERT INTO `parkwash_car_series` VALUES (981, 10698, 'G37 Convertible [G37 敞篷]');
INSERT INTO `parkwash_car_series` VALUES (982, 10698, 'M30');
INSERT INTO `parkwash_car_series` VALUES (983, 10698, 'M45');
INSERT INTO `parkwash_car_series` VALUES (984, 10698, 'Q70L Hybrid [Q70L 油电混合]');
INSERT INTO `parkwash_car_series` VALUES (985, 10698, 'QX30');
INSERT INTO `parkwash_car_series` VALUES (986, 10698, 'QX60 Hybrid [QX60 油电混合]');
INSERT INTO `parkwash_car_series` VALUES (987, 10699, '尊驰');
INSERT INTO `parkwash_car_series` VALUES (988, 10699, '骏捷FRV');
INSERT INTO `parkwash_car_series` VALUES (989, 10699, '骏捷FSV');
INSERT INTO `parkwash_car_series` VALUES (990, 10699, '骏捷Cross');
INSERT INTO `parkwash_car_series` VALUES (991, 10699, '骏捷');
INSERT INTO `parkwash_car_series` VALUES (992, 10699, '骏捷Wagon');
INSERT INTO `parkwash_car_series` VALUES (993, 10699, '酷宝');
INSERT INTO `parkwash_car_series` VALUES (994, 10699, 'H530');
INSERT INTO `parkwash_car_series` VALUES (995, 10699, 'V5');
INSERT INTO `parkwash_car_series` VALUES (996, 10699, 'H230');
INSERT INTO `parkwash_car_series` VALUES (997, 10699, 'H320');
INSERT INTO `parkwash_car_series` VALUES (998, 10699, 'H330');
INSERT INTO `parkwash_car_series` VALUES (999, 10699, 'H220');
INSERT INTO `parkwash_car_series` VALUES (1000, 10699, 'V3');
INSERT INTO `parkwash_car_series` VALUES (1001, 10699, '华颂7');
INSERT INTO `parkwash_car_series` VALUES (1002, 10699, 'H3');
INSERT INTO `parkwash_car_series` VALUES (1003, 10699, '中华豚');
INSERT INTO `parkwash_car_series` VALUES (1004, 10701, 'ES300h');
INSERT INTO `parkwash_car_series` VALUES (1005, 10701, 'ES250');
INSERT INTO `parkwash_car_series` VALUES (1006, 10701, 'ES350');
INSERT INTO `parkwash_car_series` VALUES (1007, 10701, 'LS460L');
INSERT INTO `parkwash_car_series` VALUES (1008, 10701, 'CT200h');
INSERT INTO `parkwash_car_series` VALUES (1009, 10701, 'LS600hL');
INSERT INTO `parkwash_car_series` VALUES (1010, 10701, 'LX570');
INSERT INTO `parkwash_car_series` VALUES (1011, 10701, 'IS250');
INSERT INTO `parkwash_car_series` VALUES (1012, 10701, 'IS300');
INSERT INTO `parkwash_car_series` VALUES (1013, 10701, 'RX270');
INSERT INTO `parkwash_car_series` VALUES (1014, 10701, 'GS250');
INSERT INTO `parkwash_car_series` VALUES (1015, 10701, 'GS350');
INSERT INTO `parkwash_car_series` VALUES (1016, 10701, 'GS450h');
INSERT INTO `parkwash_car_series` VALUES (1017, 10701, 'GX400');
INSERT INTO `parkwash_car_series` VALUES (1018, 10701, 'RX350');
INSERT INTO `parkwash_car_series` VALUES (1019, 10701, 'RX450h');
INSERT INTO `parkwash_car_series` VALUES (1020, 10701, 'GS300h');
INSERT INTO `parkwash_car_series` VALUES (1021, 10701, 'RC F');
INSERT INTO `parkwash_car_series` VALUES (1022, 10701, 'NX200');
INSERT INTO `parkwash_car_series` VALUES (1023, 10701, 'NX200t');
INSERT INTO `parkwash_car_series` VALUES (1024, 10701, 'NX300h');
INSERT INTO `parkwash_car_series` VALUES (1025, 10701, 'ES240');
INSERT INTO `parkwash_car_series` VALUES (1026, 10701, 'GS300');
INSERT INTO `parkwash_car_series` VALUES (1027, 10701, 'GS460');
INSERT INTO `parkwash_car_series` VALUES (1028, 10701, 'GX460');
INSERT INTO `parkwash_car_series` VALUES (1029, 10701, 'IS250 C');
INSERT INTO `parkwash_car_series` VALUES (1030, 10701, 'IS300 C');
INSERT INTO `parkwash_car_series` VALUES (1031, 10701, 'LS460');
INSERT INTO `parkwash_car_series` VALUES (1032, 10701, 'RX400h');
INSERT INTO `parkwash_car_series` VALUES (1033, 10701, 'SC430');
INSERT INTO `parkwash_car_series` VALUES (1034, 10701, 'IS200t');
INSERT INTO `parkwash_car_series` VALUES (1035, 10701, 'ES200');
INSERT INTO `parkwash_car_series` VALUES (1036, 10701, 'GS200t');
INSERT INTO `parkwash_car_series` VALUES (1037, 10701, 'HS250h');
INSERT INTO `parkwash_car_series` VALUES (1038, 10701, 'IS350');
INSERT INTO `parkwash_car_series` VALUES (1039, 10701, 'IS500 F');
INSERT INTO `parkwash_car_series` VALUES (1040, 10701, 'LF-A');
INSERT INTO `parkwash_car_series` VALUES (1041, 10701, 'RC200t');
INSERT INTO `parkwash_car_series` VALUES (1042, 10701, 'RX200t');
INSERT INTO `parkwash_car_series` VALUES (1043, 11017, 'QQ');
INSERT INTO `parkwash_car_series` VALUES (1044, 11017, 'QQ3');
INSERT INTO `parkwash_car_series` VALUES (1045, 11017, 'QQ6');
INSERT INTO `parkwash_car_series` VALUES (1046, 11017, 'QQme');
INSERT INTO `parkwash_car_series` VALUES (1047, 11017, '东方之子');
INSERT INTO `parkwash_car_series` VALUES (1048, 11017, '旗云1');
INSERT INTO `parkwash_car_series` VALUES (1049, 11017, '旗云2');
INSERT INTO `parkwash_car_series` VALUES (1050, 11017, '旗云3');
INSERT INTO `parkwash_car_series` VALUES (1051, 11017, '旗云5');
INSERT INTO `parkwash_car_series` VALUES (1052, 11017, 'A5');
INSERT INTO `parkwash_car_series` VALUES (1053, 11017, 'V5');
INSERT INTO `parkwash_car_series` VALUES (1054, 11017, 'A1');
INSERT INTO `parkwash_car_series` VALUES (1055, 11017, 'A3-两厢');
INSERT INTO `parkwash_car_series` VALUES (1056, 11017, 'A3-三厢');
INSERT INTO `parkwash_car_series` VALUES (1057, 11017, '风云2-三厢');
INSERT INTO `parkwash_car_series` VALUES (1058, 11017, '风云2-两厢');
INSERT INTO `parkwash_car_series` VALUES (1059, 11017, 'E5');
INSERT INTO `parkwash_car_series` VALUES (1060, 11017, '瑞虎5');
INSERT INTO `parkwash_car_series` VALUES (1061, 11017, '艾瑞泽7');
INSERT INTO `parkwash_car_series` VALUES (1062, 11017, 'E3');
INSERT INTO `parkwash_car_series` VALUES (1063, 11017, '瑞虎3');
INSERT INTO `parkwash_car_series` VALUES (1064, 11017, '艾瑞泽3');
INSERT INTO `parkwash_car_series` VALUES (1065, 11017, '艾瑞泽M7');
INSERT INTO `parkwash_car_series` VALUES (1066, 11017, '艾瑞泽7e');
INSERT INTO `parkwash_car_series` VALUES (1067, 11017, '艾瑞泽5');
INSERT INTO `parkwash_car_series` VALUES (1068, 11017, '瑞虎S');
INSERT INTO `parkwash_car_series` VALUES (1069, 11017, '瑞虎DR');
INSERT INTO `parkwash_car_series` VALUES (1070, 11017, '瑞虎7');
INSERT INTO `parkwash_car_series` VALUES (1071, 11017, '瑞虎');
INSERT INTO `parkwash_car_series` VALUES (1072, 11017, '瑞虎3x');
INSERT INTO `parkwash_car_series` VALUES (1073, 11017, '艾瑞泽5 SPORT');
INSERT INTO `parkwash_car_series` VALUES (1074, 11017, 'eQ');
INSERT INTO `parkwash_car_series` VALUES (1075, 11017, 'eQ1');
INSERT INTO `parkwash_car_series` VALUES (1076, 11017, '东方之子Cross');
INSERT INTO `parkwash_car_series` VALUES (1077, 11017, '旗云');
INSERT INTO `parkwash_car_series` VALUES (1078, 11017, '旗云之星');
INSERT INTO `parkwash_car_series` VALUES (1079, 11017, '爱卡');
INSERT INTO `parkwash_car_series` VALUES (1080, 11017, '瑞虎7 SPORT');
INSERT INTO `parkwash_car_series` VALUES (1081, 11017, 'M1');
INSERT INTO `parkwash_car_series` VALUES (1082, 11017, 'X1');
INSERT INTO `parkwash_car_series` VALUES (1083, 11017, 'G5');
INSERT INTO `parkwash_car_series` VALUES (1084, 11017, 'G6');
INSERT INTO `parkwash_car_series` VALUES (1085, 11017, 'G3');
INSERT INTO `parkwash_car_series` VALUES (1086, 11017, 'M5');
INSERT INTO `parkwash_car_series` VALUES (1087, 11017, 'H5');
INSERT INTO `parkwash_car_series` VALUES (1088, 11017, 'X5');
INSERT INTO `parkwash_car_series` VALUES (1089, 11017, 'V5');
INSERT INTO `parkwash_car_series` VALUES (1090, 11017, 'H3');
INSERT INTO `parkwash_car_series` VALUES (1091, 11704, '海狮');
INSERT INTO `parkwash_car_series` VALUES (1092, 11704, '智领');
INSERT INTO `parkwash_car_series` VALUES (1093, 11704, '睿翔');
INSERT INTO `parkwash_car_series` VALUES (1094, 11704, '尊领');
INSERT INTO `parkwash_car_series` VALUES (1095, 11704, '阁瑞斯');
INSERT INTO `parkwash_car_series` VALUES (1096, 11704, '御领');
INSERT INTO `parkwash_car_series` VALUES (1097, 11704, '大海狮L');
INSERT INTO `parkwash_car_series` VALUES (1098, 11704, '大海狮W');
INSERT INTO `parkwash_car_series` VALUES (1099, 11704, '大海狮LL');
INSERT INTO `parkwash_car_series` VALUES (1100, 11704, '750');
INSERT INTO `parkwash_car_series` VALUES (1101, 11704, 'F50');
INSERT INTO `parkwash_car_series` VALUES (1102, 11704, 'S50');
INSERT INTO `parkwash_car_series` VALUES (1103, 11704, 'T30');
INSERT INTO `parkwash_car_series` VALUES (1104, 11704, 'T32');
INSERT INTO `parkwash_car_series` VALUES (1105, 11704, 'T50');
INSERT INTO `parkwash_car_series` VALUES (1106, 11704, 'T52');
INSERT INTO `parkwash_car_series` VALUES (1107, 11704, '小海狮X30');
INSERT INTO `parkwash_car_series` VALUES (1108, 11704, '智尚S30');
INSERT INTO `parkwash_car_series` VALUES (1109, 11704, '海星');
INSERT INTO `parkwash_car_series` VALUES (1110, 11704, '海星A7');
INSERT INTO `parkwash_car_series` VALUES (1111, 11704, '海星A9');
INSERT INTO `parkwash_car_series` VALUES (1112, 11704, '海星T20');
INSERT INTO `parkwash_car_series` VALUES (1113, 11704, '海星T22');
INSERT INTO `parkwash_car_series` VALUES (1114, 11704, '海狮X30L');
INSERT INTO `parkwash_car_series` VALUES (1115, 11704, '海狮快运');
INSERT INTO `parkwash_car_series` VALUES (1116, 11704, '蒂阿兹');
INSERT INTO `parkwash_car_series` VALUES (1117, 11704, '金典007');
INSERT INTO `parkwash_car_series` VALUES (1118, 11704, '金典009');
INSERT INTO `parkwash_car_series` VALUES (1119, 11704, '锐驰');
INSERT INTO `parkwash_car_series` VALUES (1120, 11704, '阁瑞斯御领');
INSERT INTO `parkwash_car_series` VALUES (1121, 11704, '阁瑞斯快运');
INSERT INTO `parkwash_car_series` VALUES (1122, 11704, '阁瑞斯智领');
INSERT INTO `parkwash_car_series` VALUES (1123, 11704, '阁瑞斯睿翔');
INSERT INTO `parkwash_car_series` VALUES (1124, 11704, '雷龙');
INSERT INTO `parkwash_car_series` VALUES (1125, 11704, '霸道');
INSERT INTO `parkwash_car_series` VALUES (1126, 11704, '霸道009');
INSERT INTO `parkwash_car_series` VALUES (1127, 11704, '致尚S30');
INSERT INTO `parkwash_car_series` VALUES (1128, 11704, '致尚S35');
INSERT INTO `parkwash_car_series` VALUES (1129, 11704, '大力神');
INSERT INTO `parkwash_car_series` VALUES (1130, 11704, '大力神K3');
INSERT INTO `parkwash_car_series` VALUES (1131, 11704, '大力神K5');
INSERT INTO `parkwash_car_series` VALUES (1132, 11704, '金典009');
INSERT INTO `parkwash_car_series` VALUES (1133, 11704, '新金典');
INSERT INTO `parkwash_car_series` VALUES (1134, 11704, '智尚S30');
INSERT INTO `parkwash_car_series` VALUES (1135, 11704, '金典007');
INSERT INTO `parkwash_car_series` VALUES (1136, 12169, '杰勋');
INSERT INTO `parkwash_car_series` VALUES (1137, 12169, '奔奔MINI');
INSERT INTO `parkwash_car_series` VALUES (1138, 12169, '奔奔');
INSERT INTO `parkwash_car_series` VALUES (1139, 12169, '奔奔i');
INSERT INTO `parkwash_car_series` VALUES (1140, 12169, '奔奔LOVE');
INSERT INTO `parkwash_car_series` VALUES (1141, 12169, '志翔');
INSERT INTO `parkwash_car_series` VALUES (1142, 12169, 'CX30-两厢');
INSERT INTO `parkwash_car_series` VALUES (1143, 12169, 'CX30-三厢');
INSERT INTO `parkwash_car_series` VALUES (1144, 12169, '悦翔V3');
INSERT INTO `parkwash_car_series` VALUES (1145, 12169, '悦翔-三厢');
INSERT INTO `parkwash_car_series` VALUES (1146, 12169, '悦翔-两厢');
INSERT INTO `parkwash_car_series` VALUES (1147, 12169, '悦翔V5');
INSERT INTO `parkwash_car_series` VALUES (1148, 12169, 'CX20');
INSERT INTO `parkwash_car_series` VALUES (1149, 12169, '逸动');
INSERT INTO `parkwash_car_series` VALUES (1150, 12169, 'CS35');
INSERT INTO `parkwash_car_series` VALUES (1151, 12169, '睿骋');
INSERT INTO `parkwash_car_series` VALUES (1152, 12169, '致尚XT');
INSERT INTO `parkwash_car_series` VALUES (1153, 12169, 'CS75');
INSERT INTO `parkwash_car_series` VALUES (1154, 12169, '悦翔V7');
INSERT INTO `parkwash_car_series` VALUES (1155, 12169, '逸动XT');
INSERT INTO `parkwash_car_series` VALUES (1156, 12169, 'CS15');
INSERT INTO `parkwash_car_series` VALUES (1157, 12169, 'CS95');
INSERT INTO `parkwash_car_series` VALUES (1158, 12169, '凌轩');
INSERT INTO `parkwash_car_series` VALUES (1159, 12169, '奔奔EV');
INSERT INTO `parkwash_car_series` VALUES (1160, 12169, '逸动EV');
INSERT INTO `parkwash_car_series` VALUES (1161, 12489, '绅宝D50');
INSERT INTO `parkwash_car_series` VALUES (1162, 12489, '绅宝D20');
INSERT INTO `parkwash_car_series` VALUES (1163, 12489, '绅宝D70');
INSERT INTO `parkwash_car_series` VALUES (1164, 12489, '绅宝X65');
INSERT INTO `parkwash_car_series` VALUES (1165, 12489, 'E系列');
INSERT INTO `parkwash_car_series` VALUES (1166, 12489, 'BJ40');
INSERT INTO `parkwash_car_series` VALUES (1167, 12489, '绅宝');
INSERT INTO `parkwash_car_series` VALUES (1168, 12489, '绅宝D60');
INSERT INTO `parkwash_car_series` VALUES (1169, 12489, '绅宝D80');
INSERT INTO `parkwash_car_series` VALUES (1170, 12489, '绅宝CC');
INSERT INTO `parkwash_car_series` VALUES (1171, 12489, '绅宝X25');
INSERT INTO `parkwash_car_series` VALUES (1172, 12489, '绅宝X55');
INSERT INTO `parkwash_car_series` VALUES (1173, 12489, 'BJ40L');
INSERT INTO `parkwash_car_series` VALUES (1174, 12489, 'BJ80');
INSERT INTO `parkwash_car_series` VALUES (1175, 12489, '绅宝X35');
INSERT INTO `parkwash_car_series` VALUES (1176, 12489, 'BJ20');
INSERT INTO `parkwash_car_series` VALUES (1177, 12489, 'EC180');
INSERT INTO `parkwash_car_series` VALUES (1178, 12489, 'EH300');
INSERT INTO `parkwash_car_series` VALUES (1179, 12489, 'ES210');
INSERT INTO `parkwash_car_series` VALUES (1180, 12489, 'EU260');
INSERT INTO `parkwash_car_series` VALUES (1181, 12489, 'EU400');
INSERT INTO `parkwash_car_series` VALUES (1182, 12489, 'EV160');
INSERT INTO `parkwash_car_series` VALUES (1183, 12489, 'EV200');
INSERT INTO `parkwash_car_series` VALUES (1184, 12489, 'EX200');
INSERT INTO `parkwash_car_series` VALUES (1185, 12489, 'EX260');
INSERT INTO `parkwash_car_series` VALUES (1186, 12489, 'E150EV');
INSERT INTO `parkwash_car_series` VALUES (1187, 12572, '乐驰');
INSERT INTO `parkwash_car_series` VALUES (1188, 12572, '宝骏630');
INSERT INTO `parkwash_car_series` VALUES (1189, 12572, '宝骏610');
INSERT INTO `parkwash_car_series` VALUES (1190, 12572, '宝骏730');
INSERT INTO `parkwash_car_series` VALUES (1191, 12572, '宝骏560');
INSERT INTO `parkwash_car_series` VALUES (1192, 12572, '宝骏310');
INSERT INTO `parkwash_car_series` VALUES (1193, 12572, '宝骏330');
INSERT INTO `parkwash_car_series` VALUES (1194, 12572, '宝骏510');
INSERT INTO `parkwash_car_series` VALUES (1195, 12572, '610 Cross');
INSERT INTO `parkwash_car_series` VALUES (1196, 12688, 'MDX');
INSERT INTO `parkwash_car_series` VALUES (1197, 12688, 'RL');
INSERT INTO `parkwash_car_series` VALUES (1198, 12688, 'TL');
INSERT INTO `parkwash_car_series` VALUES (1199, 12688, 'ZDX');
INSERT INTO `parkwash_car_series` VALUES (1200, 12688, 'RDX');
INSERT INTO `parkwash_car_series` VALUES (1201, 12688, 'ILX Hybrid');
INSERT INTO `parkwash_car_series` VALUES (1202, 12688, 'RLX');
INSERT INTO `parkwash_car_series` VALUES (1203, 12688, 'ILX');
INSERT INTO `parkwash_car_series` VALUES (1204, 12688, 'TLX');
INSERT INTO `parkwash_car_series` VALUES (1205, 12688, 'CDX');
INSERT INTO `parkwash_car_series` VALUES (1206, 12688, 'RLX Hybrid');
INSERT INTO `parkwash_car_series` VALUES (1207, 12688, 'TSX');
INSERT INTO `parkwash_car_series` VALUES (1208, 12749, 'Fortwo Coupe');
INSERT INTO `parkwash_car_series` VALUES (1209, 12749, 'Fortwo Cabrio');
INSERT INTO `parkwash_car_series` VALUES (1210, 12749, 'Forfour');
INSERT INTO `parkwash_car_series` VALUES (1211, 12749, 'Fortwo');
INSERT INTO `parkwash_car_series` VALUES (1212, 12749, 'Fortwo Electric Drive');
INSERT INTO `parkwash_car_series` VALUES (1213, 12827, '传祺');
INSERT INTO `parkwash_car_series` VALUES (1214, 12827, 'GS5');
INSERT INTO `parkwash_car_series` VALUES (1215, 12827, 'GA5');
INSERT INTO `parkwash_car_series` VALUES (1216, 12827, 'GA3');
INSERT INTO `parkwash_car_series` VALUES (1217, 12827, 'GA3S 视界');
INSERT INTO `parkwash_car_series` VALUES (1218, 12827, 'GS5 速博');
INSERT INTO `parkwash_car_series` VALUES (1219, 12827, 'GA5 增程式电动车');
INSERT INTO `parkwash_car_series` VALUES (1220, 12827, 'GA6');
INSERT INTO `parkwash_car_series` VALUES (1221, 12827, 'GS4');
INSERT INTO `parkwash_car_series` VALUES (1222, 12827, 'GA8');
INSERT INTO `parkwash_car_series` VALUES (1223, 12827, 'GS8');
INSERT INTO `parkwash_car_series` VALUES (1224, 12827, 'GA3S PHEV');
INSERT INTO `parkwash_car_series` VALUES (1225, 12827, 'GS4 PHEV');
INSERT INTO `parkwash_car_series` VALUES (1226, 13447, 'D50');
INSERT INTO `parkwash_car_series` VALUES (1227, 13447, 'R50');
INSERT INTO `parkwash_car_series` VALUES (1228, 13447, 'R50X');
INSERT INTO `parkwash_car_series` VALUES (1229, 13447, 'R30');
INSERT INTO `parkwash_car_series` VALUES (1230, 13447, 'T70');
INSERT INTO `parkwash_car_series` VALUES (1231, 13447, 'T70X');
INSERT INTO `parkwash_car_series` VALUES (1232, 13447, 'T90');
INSERT INTO `parkwash_car_series` VALUES (1233, 13447, 'M50V');
INSERT INTO `parkwash_car_series` VALUES (1234, 13447, '晨风');
INSERT INTO `parkwash_car_series` VALUES (1235, 13520, '圣达菲');
INSERT INTO `parkwash_car_series` VALUES (1236, 13520, '圣达菲C9');
INSERT INTO `parkwash_car_series` VALUES (1237, 13520, '宝利格');
INSERT INTO `parkwash_car_series` VALUES (1238, 13520, '路盛E70');
INSERT INTO `parkwash_car_series` VALUES (1239, 13520, '特拉卡');
INSERT INTO `parkwash_car_series` VALUES (1240, 13520, '特拉卡T9');
INSERT INTO `parkwash_car_series` VALUES (1241, 13520, 'B11');
INSERT INTO `parkwash_car_series` VALUES (1242, 13520, 'EV160B');
INSERT INTO `parkwash_car_series` VALUES (1243, 13520, 'iEV230');
INSERT INTO `parkwash_car_series` VALUES (1244, 13520, 'XEV260');
INSERT INTO `parkwash_car_series` VALUES (1245, 13520, '路盛E80');
INSERT INTO `parkwash_car_series` VALUES (1246, 13635, '森雅');
INSERT INTO `parkwash_car_series` VALUES (1247, 13635, '佳宝');
INSERT INTO `parkwash_car_series` VALUES (1248, 13635, '森雅M80');
INSERT INTO `parkwash_car_series` VALUES (1249, 13635, '森雅S80');
INSERT INTO `parkwash_car_series` VALUES (1250, 13635, 'V80');
INSERT INTO `parkwash_car_series` VALUES (1251, 13635, 'V80L');
INSERT INTO `parkwash_car_series` VALUES (1252, 13635, 'V77');
INSERT INTO `parkwash_car_series` VALUES (1253, 13635, 'V75');
INSERT INTO `parkwash_car_series` VALUES (1254, 13635, '森雅R7');
INSERT INTO `parkwash_car_series` VALUES (1255, 13635, 'AV6');
INSERT INTO `parkwash_car_series` VALUES (1256, 13635, 'V52');
INSERT INTO `parkwash_car_series` VALUES (1257, 13635, 'V55');
INSERT INTO `parkwash_car_series` VALUES (1258, 13635, 'V70');
INSERT INTO `parkwash_car_series` VALUES (1259, 13635, 'V70Ⅱ代');
INSERT INTO `parkwash_car_series` VALUES (1260, 13635, '福星');
INSERT INTO `parkwash_car_series` VALUES (1261, 14335, '菱智V3');
INSERT INTO `parkwash_car_series` VALUES (1262, 14335, '菱智');
INSERT INTO `parkwash_car_series` VALUES (1263, 14335, '菱智M3');
INSERT INTO `parkwash_car_series` VALUES (1264, 14335, '菱智M5 D19');
INSERT INTO `parkwash_car_series` VALUES (1265, 14335, '菱智Q7');
INSERT INTO `parkwash_car_series` VALUES (1266, 14335, '菱智Q3');
INSERT INTO `parkwash_car_series` VALUES (1267, 14335, '菱智M5 Q3');
INSERT INTO `parkwash_car_series` VALUES (1268, 14335, '菱智M5 Q7');
INSERT INTO `parkwash_car_series` VALUES (1269, 14335, '菱智Q8');
INSERT INTO `parkwash_car_series` VALUES (1270, 14335, '菱智QA');
INSERT INTO `parkwash_car_series` VALUES (1271, 14335, '菱智M5 QA');
INSERT INTO `parkwash_car_series` VALUES (1272, 14335, '景逸XL');
INSERT INTO `parkwash_car_series` VALUES (1273, 14335, '景逸LV');
INSERT INTO `parkwash_car_series` VALUES (1274, 14335, '景逸');
INSERT INTO `parkwash_car_series` VALUES (1275, 14335, '景逸SUV');
INSERT INTO `parkwash_car_series` VALUES (1276, 14335, '景逸TT');
INSERT INTO `parkwash_car_series` VALUES (1277, 14335, '菱通');
INSERT INTO `parkwash_car_series` VALUES (1278, 14335, '菱越');
INSERT INTO `parkwash_car_series` VALUES (1279, 14335, '景逸X5');
INSERT INTO `parkwash_car_series` VALUES (1280, 14335, '景逸Cross');
INSERT INTO `parkwash_car_series` VALUES (1281, 14335, '景逸X3');
INSERT INTO `parkwash_car_series` VALUES (1282, 14335, 'CM7');
INSERT INTO `parkwash_car_series` VALUES (1283, 14335, '景逸S50');
INSERT INTO `parkwash_car_series` VALUES (1284, 14335, '景逸XV');
INSERT INTO `parkwash_car_series` VALUES (1285, 14335, 'S500');
INSERT INTO `parkwash_car_series` VALUES (1286, 14335, '菱智M5');
INSERT INTO `parkwash_car_series` VALUES (1287, 14335, '菱智M3L');
INSERT INTO `parkwash_car_series` VALUES (1288, 14335, 'SX6');
INSERT INTO `parkwash_car_series` VALUES (1289, 14335, 'F600');
INSERT INTO `parkwash_car_series` VALUES (1290, 14335, 'F600L');
INSERT INTO `parkwash_car_series` VALUES (1291, 14335, '菱智M5L');
INSERT INTO `parkwash_car_series` VALUES (1292, 14784, '大7 SUV');
INSERT INTO `parkwash_car_series` VALUES (1293, 14784, 'MASTER CEO');
INSERT INTO `parkwash_car_series` VALUES (1294, 14784, '大7 MPV');
INSERT INTO `parkwash_car_series` VALUES (1295, 14784, '5 Sedan');
INSERT INTO `parkwash_car_series` VALUES (1296, 14784, '优6 SUV');
INSERT INTO `parkwash_car_series` VALUES (1297, 14784, '纳5');
INSERT INTO `parkwash_car_series` VALUES (1298, 14784, '锐3');
INSERT INTO `parkwash_car_series` VALUES (1299, 14876, '豪情');
INSERT INTO `parkwash_car_series` VALUES (1300, 14876, '美日之星');
INSERT INTO `parkwash_car_series` VALUES (1301, 14876, '优利欧');
INSERT INTO `parkwash_car_series` VALUES (1302, 14876, '美人豹');
INSERT INTO `parkwash_car_series` VALUES (1303, 14876, 'SC3');
INSERT INTO `parkwash_car_series` VALUES (1304, 14876, '远景');
INSERT INTO `parkwash_car_series` VALUES (1305, 14876, 'GX7');
INSERT INTO `parkwash_car_series` VALUES (1306, 14876, '帝豪-两厢');
INSERT INTO `parkwash_car_series` VALUES (1307, 14876, '帝豪-三厢');
INSERT INTO `parkwash_car_series` VALUES (1308, 14876, '海景');
INSERT INTO `parkwash_car_series` VALUES (1309, 14876, '豪情SUV');
INSERT INTO `parkwash_car_series` VALUES (1310, 14876, '自由舰');
INSERT INTO `parkwash_car_series` VALUES (1311, 14876, '金刚财富');
INSERT INTO `parkwash_car_series` VALUES (1312, 14876, 'EC8');
INSERT INTO `parkwash_car_series` VALUES (1313, 14876, '熊猫');
INSERT INTO `parkwash_car_series` VALUES (1314, 14876, '金刚');
INSERT INTO `parkwash_car_series` VALUES (1315, 14876, 'SX7');
INSERT INTO `parkwash_car_series` VALUES (1316, 14876, 'SC5');
INSERT INTO `parkwash_car_series` VALUES (1317, 14876, 'SC6');
INSERT INTO `parkwash_car_series` VALUES (1318, 14876, 'SC7');
INSERT INTO `parkwash_car_series` VALUES (1319, 14876, 'GC7');
INSERT INTO `parkwash_car_series` VALUES (1320, 14876, 'GX2');
INSERT INTO `parkwash_car_series` VALUES (1321, 14876, '博瑞');
INSERT INTO `parkwash_car_series` VALUES (1322, 14876, '帝豪GS');
INSERT INTO `parkwash_car_series` VALUES (1323, 14876, '帝豪GL');
INSERT INTO `parkwash_car_series` VALUES (1324, 14876, '博越');
INSERT INTO `parkwash_car_series` VALUES (1325, 14876, '远景SUV');
INSERT INTO `parkwash_car_series` VALUES (1326, 14876, '帝豪EV-三厢');
INSERT INTO `parkwash_car_series` VALUES (1327, 14876, '美日-三厢');
INSERT INTO `parkwash_car_series` VALUES (1328, 14876, '美日-两厢');
INSERT INTO `parkwash_car_series` VALUES (1329, 14876, '远景X1');
INSERT INTO `parkwash_car_series` VALUES (1330, 14876, '中国龙');
INSERT INTO `parkwash_car_series` VALUES (1331, 14876, 'EC7');
INSERT INTO `parkwash_car_series` VALUES (1332, 14876, 'EC7-RV');
INSERT INTO `parkwash_car_series` VALUES (1333, 15225, 'V3菱悦');
INSERT INTO `parkwash_car_series` VALUES (1334, 15225, 'V5菱致');
INSERT INTO `parkwash_car_series` VALUES (1335, 15225, 'V6菱仕');
INSERT INTO `parkwash_car_series` VALUES (1336, 15225, '得利卡');
INSERT INTO `parkwash_car_series` VALUES (1337, 15225, '菱帅');
INSERT INTO `parkwash_car_series` VALUES (1338, 15225, '菱动');
INSERT INTO `parkwash_car_series` VALUES (1339, 15225, '希旺');
INSERT INTO `parkwash_car_series` VALUES (1340, 15225, 'DX7');
INSERT INTO `parkwash_car_series` VALUES (1341, 15225, 'DX3');
INSERT INTO `parkwash_car_series` VALUES (1342, 15225, '菱利');
INSERT INTO `parkwash_car_series` VALUES (1343, 15788, 'S2');
INSERT INTO `parkwash_car_series` VALUES (1344, 15788, 'S3');
INSERT INTO `parkwash_car_series` VALUES (1345, 15788, 'H2');
INSERT INTO `parkwash_car_series` VALUES (1346, 15788, 'H3');
INSERT INTO `parkwash_car_series` VALUES (1347, 15788, 'H2E');
INSERT INTO `parkwash_car_series` VALUES (1348, 15788, 'S6');
INSERT INTO `parkwash_car_series` VALUES (1349, 15788, 'H3F');
INSERT INTO `parkwash_car_series` VALUES (1350, 15788, 'H2V');
INSERT INTO `parkwash_car_series` VALUES (1351, 15788, 'S3L');
INSERT INTO `parkwash_car_series` VALUES (1352, 15788, 'H6');
INSERT INTO `parkwash_car_series` VALUES (1353, 15788, 'S5');
INSERT INTO `parkwash_car_series` VALUES (1354, 16627, 'Clubman');
INSERT INTO `parkwash_car_series` VALUES (1355, 16627, 'Cabrio');
INSERT INTO `parkwash_car_series` VALUES (1356, 16627, 'Countryman');
INSERT INTO `parkwash_car_series` VALUES (1357, 16627, 'Paceman');
INSERT INTO `parkwash_car_series` VALUES (1358, 16627, 'MINI JCW Clubman');
INSERT INTO `parkwash_car_series` VALUES (1359, 16627, 'MINI JCW');
INSERT INTO `parkwash_car_series` VALUES (1360, 16627, 'MINI JCW Coupe');
INSERT INTO `parkwash_car_series` VALUES (1361, 16627, 'MINI JCW Paceman');
INSERT INTO `parkwash_car_series` VALUES (1362, 16627, 'MINI JCW Countryman');
INSERT INTO `parkwash_car_series` VALUES (1363, 16627, 'MINI');
INSERT INTO `parkwash_car_series` VALUES (1364, 16627, 'MINI 五门版');
INSERT INTO `parkwash_car_series` VALUES (1365, 16627, 'Coupe');
INSERT INTO `parkwash_car_series` VALUES (1366, 16627, 'Roadster');
INSERT INTO `parkwash_car_series` VALUES (1367, 16627, 'MINI JCW Cabrio');
INSERT INTO `parkwash_car_series` VALUES (1368, 16774, '力帆520');
INSERT INTO `parkwash_car_series` VALUES (1369, 16774, '力帆520i');
INSERT INTO `parkwash_car_series` VALUES (1370, 16774, '力帆320');
INSERT INTO `parkwash_car_series` VALUES (1371, 16774, '力帆620');
INSERT INTO `parkwash_car_series` VALUES (1372, 16774, '丰顺');
INSERT INTO `parkwash_car_series` VALUES (1373, 16774, '兴顺');
INSERT INTO `parkwash_car_series` VALUES (1374, 16774, 'X60');
INSERT INTO `parkwash_car_series` VALUES (1375, 16774, '力帆720');
INSERT INTO `parkwash_car_series` VALUES (1376, 16774, '福顺');
INSERT INTO `parkwash_car_series` VALUES (1377, 16774, '力帆330');
INSERT INTO `parkwash_car_series` VALUES (1378, 16774, '力帆530');
INSERT INTO `parkwash_car_series` VALUES (1379, 16774, '力帆630');
INSERT INTO `parkwash_car_series` VALUES (1380, 16774, 'X50');
INSERT INTO `parkwash_car_series` VALUES (1381, 16774, '乐途');
INSERT INTO `parkwash_car_series` VALUES (1382, 16774, '乐途S');
INSERT INTO `parkwash_car_series` VALUES (1383, 16774, '力帆820');
INSERT INTO `parkwash_car_series` VALUES (1384, 16774, 'X80');
INSERT INTO `parkwash_car_series` VALUES (1385, 16774, '轩朗');
INSERT INTO `parkwash_car_series` VALUES (1386, 16774, '迈威');
INSERT INTO `parkwash_car_series` VALUES (1387, 17681, 'K01');
INSERT INTO `parkwash_car_series` VALUES (1388, 17681, 'K06');
INSERT INTO `parkwash_car_series` VALUES (1389, 17681, 'K07 II');
INSERT INTO `parkwash_car_series` VALUES (1390, 17681, 'K02');
INSERT INTO `parkwash_car_series` VALUES (1391, 17681, 'K17');
INSERT INTO `parkwash_car_series` VALUES (1392, 17681, 'K07');
INSERT INTO `parkwash_car_series` VALUES (1393, 17681, 'V27');
INSERT INTO `parkwash_car_series` VALUES (1394, 17681, 'V22');
INSERT INTO `parkwash_car_series` VALUES (1395, 17681, 'V21');
INSERT INTO `parkwash_car_series` VALUES (1396, 17681, 'V07S');
INSERT INTO `parkwash_car_series` VALUES (1397, 17681, 'V29');
INSERT INTO `parkwash_car_series` VALUES (1398, 17681, 'C37');
INSERT INTO `parkwash_car_series` VALUES (1399, 17681, 'V26');
INSERT INTO `parkwash_car_series` VALUES (1400, 17681, 'C35');
INSERT INTO `parkwash_car_series` VALUES (1401, 17681, '风光');
INSERT INTO `parkwash_car_series` VALUES (1402, 17681, 'C36');
INSERT INTO `parkwash_car_series` VALUES (1403, 17681, '风光330');
INSERT INTO `parkwash_car_series` VALUES (1404, 17681, '风光350');
INSERT INTO `parkwash_car_series` VALUES (1405, 17681, '风光360');
INSERT INTO `parkwash_car_series` VALUES (1406, 17681, '风光370');
INSERT INTO `parkwash_car_series` VALUES (1407, 17681, 'K07S');
INSERT INTO `parkwash_car_series` VALUES (1408, 17681, '风光580');
INSERT INTO `parkwash_car_series` VALUES (1409, 17898, '瑞风祥和');
INSERT INTO `parkwash_car_series` VALUES (1410, 17898, '瑞风穿梭');
INSERT INTO `parkwash_car_series` VALUES (1411, 17898, '瑞风II和畅');
INSERT INTO `parkwash_car_series` VALUES (1412, 17898, '瑞风');
INSERT INTO `parkwash_car_series` VALUES (1413, 17898, '瑞风I');
INSERT INTO `parkwash_car_series` VALUES (1414, 17898, '瑞风一家亲');
INSERT INTO `parkwash_car_series` VALUES (1415, 17898, '瑞风II');
INSERT INTO `parkwash_car_series` VALUES (1416, 17898, '瑞风彩色之旅');
INSERT INTO `parkwash_car_series` VALUES (1417, 17898, '和悦');
INSERT INTO `parkwash_car_series` VALUES (1418, 17898, '和悦RS');
INSERT INTO `parkwash_car_series` VALUES (1419, 17898, '瑞风M5');
INSERT INTO `parkwash_car_series` VALUES (1420, 17898, '瑞风S5');
INSERT INTO `parkwash_car_series` VALUES (1421, 17898, '瑞风S3');
INSERT INTO `parkwash_car_series` VALUES (1422, 17898, '瑞风M3');
INSERT INTO `parkwash_car_series` VALUES (1423, 17898, '瑞风S2');
INSERT INTO `parkwash_car_series` VALUES (1424, 17898, 'iEV4');
INSERT INTO `parkwash_car_series` VALUES (1425, 17898, 'iEV5');
INSERT INTO `parkwash_car_series` VALUES (1426, 17898, 'iEV6E');
INSERT INTO `parkwash_car_series` VALUES (1427, 17898, 'iEV6S');
INSERT INTO `parkwash_car_series` VALUES (1428, 17898, 'iEV7');
INSERT INTO `parkwash_car_series` VALUES (1429, 17898, '同悦');
INSERT INTO `parkwash_car_series` VALUES (1430, 17898, '同悦Cross');
INSERT INTO `parkwash_car_series` VALUES (1431, 17898, '同悦RS');
INSERT INTO `parkwash_car_series` VALUES (1432, 17898, '和悦A13');
INSERT INTO `parkwash_car_series` VALUES (1433, 17898, '和悦A13 Cross');
INSERT INTO `parkwash_car_series` VALUES (1434, 17898, '和悦A13 RS');
INSERT INTO `parkwash_car_series` VALUES (1435, 17898, '和悦A30');
INSERT INTO `parkwash_car_series` VALUES (1436, 17898, '和悦iEV4');
INSERT INTO `parkwash_car_series` VALUES (1437, 17898, '宾悦');
INSERT INTO `parkwash_car_series` VALUES (1438, 17898, '帅铃T6');
INSERT INTO `parkwash_car_series` VALUES (1439, 17898, '悦悦');
INSERT INTO `parkwash_car_series` VALUES (1440, 17898, '悦悦Cross');
INSERT INTO `parkwash_car_series` VALUES (1441, 17898, '悦悦J2');
INSERT INTO `parkwash_car_series` VALUES (1442, 17898, '星锐');
INSERT INTO `parkwash_car_series` VALUES (1443, 17898, '星锐4系');
INSERT INTO `parkwash_car_series` VALUES (1444, 17898, '星锐5系');
INSERT INTO `parkwash_car_series` VALUES (1445, 17898, '星锐6系');
INSERT INTO `parkwash_car_series` VALUES (1446, 17898, '瑞铃');
INSERT INTO `parkwash_car_series` VALUES (1447, 17898, '瑞铃V1');
INSERT INTO `parkwash_car_series` VALUES (1448, 17898, '瑞铃V3');
INSERT INTO `parkwash_car_series` VALUES (1449, 17898, '瑞铃V5');
INSERT INTO `parkwash_car_series` VALUES (1450, 17898, '瑞风A60');
INSERT INTO `parkwash_car_series` VALUES (1451, 17898, '瑞风M4');
INSERT INTO `parkwash_car_series` VALUES (1452, 17898, '瑞风S2mini');
INSERT INTO `parkwash_car_series` VALUES (1453, 17898, '瑞风S7');
INSERT INTO `parkwash_car_series` VALUES (1454, 17898, '瑞驰K3');
INSERT INTO `parkwash_car_series` VALUES (1455, 17898, '瑞驰K5');
INSERT INTO `parkwash_car_series` VALUES (1456, 17898, '瑞鹰');
INSERT INTO `parkwash_car_series` VALUES (1457, 18591, '众泰5008');
INSERT INTO `parkwash_car_series` VALUES (1458, 18591, '众泰2008');
INSERT INTO `parkwash_car_series` VALUES (1459, 18591, '江南TT');
INSERT INTO `parkwash_car_series` VALUES (1460, 18591, 'V10');
INSERT INTO `parkwash_car_series` VALUES (1461, 18591, 'Z300');
INSERT INTO `parkwash_car_series` VALUES (1462, 18591, 'T200');
INSERT INTO `parkwash_car_series` VALUES (1463, 18591, 'Z100');
INSERT INTO `parkwash_car_series` VALUES (1464, 18591, 'T600');
INSERT INTO `parkwash_car_series` VALUES (1465, 18591, 'Z500');
INSERT INTO `parkwash_car_series` VALUES (1466, 18591, '大迈X5');
INSERT INTO `parkwash_car_series` VALUES (1467, 18591, '大迈X7');
INSERT INTO `parkwash_car_series` VALUES (1468, 18591, 'Z700');
INSERT INTO `parkwash_car_series` VALUES (1469, 18591, 'SR7');
INSERT INTO `parkwash_car_series` VALUES (1470, 18591, 'SR9');
INSERT INTO `parkwash_car_series` VALUES (1471, 18591, 'E20 知豆');
INSERT INTO `parkwash_car_series` VALUES (1472, 18591, 'E200');
INSERT INTO `parkwash_car_series` VALUES (1473, 18591, 'M300');
INSERT INTO `parkwash_car_series` VALUES (1474, 18591, 'T600 Coupe');
INSERT INTO `parkwash_car_series` VALUES (1475, 18591, 'T700');
INSERT INTO `parkwash_car_series` VALUES (1476, 18591, 'Z200');
INSERT INTO `parkwash_car_series` VALUES (1477, 18591, 'Z200HB');
INSERT INTO `parkwash_car_series` VALUES (1478, 18591, 'Z360');
INSERT INTO `parkwash_car_series` VALUES (1479, 18591, 'Z500EV');
INSERT INTO `parkwash_car_series` VALUES (1480, 18591, 'Z560');
INSERT INTO `parkwash_car_series` VALUES (1481, 18591, '云100');
INSERT INTO `parkwash_car_series` VALUES (1482, 18591, '云100S');
INSERT INTO `parkwash_car_series` VALUES (1483, 18591, '芝麻');
INSERT INTO `parkwash_car_series` VALUES (1484, 18591, '芝麻E30');
INSERT INTO `parkwash_car_series` VALUES (1485, 18591, 'T300');
INSERT INTO `parkwash_car_series` VALUES (1486, 18950, 'H30');
INSERT INTO `parkwash_car_series` VALUES (1487, 18950, 'H30 Cross');
INSERT INTO `parkwash_car_series` VALUES (1488, 18950, 'S30-三厢');
INSERT INTO `parkwash_car_series` VALUES (1489, 18950, 'A60');
INSERT INTO `parkwash_car_series` VALUES (1490, 18950, 'S30');
INSERT INTO `parkwash_car_series` VALUES (1491, 18950, 'A30');
INSERT INTO `parkwash_car_series` VALUES (1492, 18950, 'AX7');
INSERT INTO `parkwash_car_series` VALUES (1493, 18950, 'L60');
INSERT INTO `parkwash_car_series` VALUES (1494, 18950, 'A9');
INSERT INTO `parkwash_car_series` VALUES (1495, 18950, 'AX3');
INSERT INTO `parkwash_car_series` VALUES (1496, 18950, 'AX5');
INSERT INTO `parkwash_car_series` VALUES (1497, 18950, 'E30L');
INSERT INTO `parkwash_car_series` VALUES (1498, 19185, '威旺306');
INSERT INTO `parkwash_car_series` VALUES (1499, 19185, '威旺205');
INSERT INTO `parkwash_car_series` VALUES (1500, 19185, 'M20');
INSERT INTO `parkwash_car_series` VALUES (1501, 19185, '威旺307');
INSERT INTO `parkwash_car_series` VALUES (1502, 19185, 'T205-D');
INSERT INTO `parkwash_car_series` VALUES (1503, 19185, '威旺007');
INSERT INTO `parkwash_car_series` VALUES (1504, 19185, '威旺M30');
INSERT INTO `parkwash_car_series` VALUES (1505, 19185, '威旺M35');
INSERT INTO `parkwash_car_series` VALUES (1506, 19185, 'S50');
INSERT INTO `parkwash_car_series` VALUES (1507, 19185, '306');
INSERT INTO `parkwash_car_series` VALUES (1508, 19185, '307');
INSERT INTO `parkwash_car_series` VALUES (1509, 19185, 'M30');
INSERT INTO `parkwash_car_series` VALUES (1510, 19185, 'M35');
INSERT INTO `parkwash_car_series` VALUES (1511, 19185, 'M50F');
INSERT INTO `parkwash_car_series` VALUES (1512, 19957, '红旗盛世');
INSERT INTO `parkwash_car_series` VALUES (1513, 19957, '红旗H7');
INSERT INTO `parkwash_car_series` VALUES (1514, 22828, 'MKZ');
INSERT INTO `parkwash_car_series` VALUES (1515, 22828, 'MKC');
INSERT INTO `parkwash_car_series` VALUES (1516, 22828, 'MKX');
INSERT INTO `parkwash_car_series` VALUES (1517, 22828, '林肯大陆');
INSERT INTO `parkwash_car_series` VALUES (1518, 22828, '领航员');
INSERT INTO `parkwash_car_series` VALUES (1519, 22828, 'MKS');
INSERT INTO `parkwash_car_series` VALUES (1520, 22828, 'MKT');
INSERT INTO `parkwash_car_series` VALUES (1521, 22828, '城市');

-- ----------------------------
-- Table structure for parkwash_card
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_card`;
CREATE TABLE `parkwash_card`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车牌号',
  `start_time` datetime(0) NULL DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime(0) NULL DEFAULT NULL COMMENT '到期时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `status` tinyint(1) NULL DEFAULT NULL COMMENT '状态 1启用 0禁用',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`uid`, `car_number`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '卡包' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for parkwash_card_record
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_card_record`;
CREATE TABLE `parkwash_card_record`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `user_tel` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用户手机号',
  `car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车牌号',
  `card_type_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '卡类型ID',
  `money` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '缴费金额(分)',
  `start_time` datetime(0) NULL DEFAULT NULL COMMENT '充值开始时间',
  `end_time` datetime(0) NULL DEFAULT NULL COMMENT '充值截止时间',
  `duration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '充值时长',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '卡包缴费记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for parkwash_card_type
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_card_type`;
CREATE TABLE `parkwash_card_type`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '卡名称',
  `price` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '价格 (分)',
  `months` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '有效月数',
  `days` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '有效天数',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `sort` mediumint(9) NULL DEFAULT 0 COMMENT '排序 由大到小',
  `status` tinyint(1) NULL DEFAULT NULL COMMENT '状态 1启用 0禁用',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '卡包类型' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of parkwash_card_type
-- ----------------------------
INSERT INTO `parkwash_card_type` VALUES (1, '月卡', 1, 0, 30, '2019-04-26 15:39:57', '2019-04-18 17:18:15', 0, 1);
INSERT INTO `parkwash_card_type` VALUES (2, '季卡', 3, 0, 90, '2019-04-26 15:30:14', '2019-04-18 17:18:15', 0, 1);
INSERT INTO `parkwash_card_type` VALUES (3, '年卡', 12, 0, 365, '2019-04-26 15:30:57', '2019-04-18 17:18:15', 0, 1);

-- ----------------------------
-- Table structure for parkwash_carport
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_carport`;
CREATE TABLE `parkwash_carport`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `buy_date` date NULL DEFAULT NULL COMMENT '购买日期',
  `car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车牌号',
  `mileage` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '行驶里程',
  `brand_id` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '品牌ID',
  `series_id` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '车系ID',
  `area_id` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '区域ID',
  `place` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车位号',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '车辆简称',
  `vip_expire` datetime(0) NULL DEFAULT NULL COMMENT 'vip到期时间',
  `isdefault` tinyint(1) NULL DEFAULT 0 COMMENT '默认车辆',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`uid`, `car_number`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '我的车辆' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for parkwash_item
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_item`;
CREATE TABLE `parkwash_item`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '洗车项目名',
  `price` mediumint(10) UNSIGNED NULL DEFAULT NULL COMMENT '价格（分）',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '洗车套餐' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of parkwash_item
-- ----------------------------
INSERT INTO `parkwash_item` VALUES (1, '车辆外观', 1000);

-- ----------------------------
-- Table structure for parkwash_notice
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_notice`;
CREATE TABLE `parkwash_notice`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receiver` tinyint(1) NULL DEFAULT NULL COMMENT '1 用户通知 2 商家通知',
  `notice_type` tinyint(1) NULL DEFAULT 0 COMMENT '1 短信 2 播报器 3微信模板消息',
  `orderid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单id',
  `store_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '门店ID',
  `uid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `tel` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '手机号',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '通知标题',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '外部链接',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '通知内容',
  `is_read` tinyint(1) NULL DEFAULT 0 COMMENT '是否已读',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE,
  INDEX `store_id`(`store_id`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for parkwash_order
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_order`;
CREATE TABLE `parkwash_order`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `adcode` mediumint(5) UNSIGNED NULL DEFAULT 0 COMMENT '城市',
  `xc_trade_id` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '自助洗车-交易单ID',
  `pool_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '排号',
  `store_id` mediumint(10) UNSIGNED NULL DEFAULT NULL COMMENT '门店',
  `uid` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '用户ID',
  `user_tel` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用户手机号',
  `car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车牌号',
  `brand_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '品牌',
  `series_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '车系',
  `area_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '停车场区域',
  `place` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车位号',
  `pay` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '订单支付金额：分，订单实际金额 = pay + deduct',
  `deduct` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '抵扣金额（分）',
  `payway` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '付款方式 cbpay车币 wxpayjs微信',
  `items` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '洗车套餐项目',
  `order_time` datetime(0) NULL DEFAULT NULL COMMENT '预约时间',
  `abort_time` datetime(0) NULL DEFAULT NULL COMMENT '截止时间',
  `service_time` datetime(0) NULL DEFAULT NULL COMMENT '开始服务时间',
  `complete_time` datetime(0) NULL DEFAULT NULL COMMENT '服务完成时间',
  `cancel_time` datetime(0) NULL DEFAULT NULL COMMENT '取消订单时间',
  `confirm_time` datetime(0) NULL DEFAULT NULL COMMENT '确认完成时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '下单时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新状态时间',
  `entry_park_time` datetime(0) NULL DEFAULT NULL COMMENT '入场车秘停车场时间',
  `entry_park_id` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '入场车秘停车场ID',
  `entry_order_sn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '入场车秘停车场order_sn',
  `fail_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '异常原因',
  `status` tinyint(4) NULL DEFAULT 0 COMMENT '-1已取消 0未支付 1已支付 3服务中 4已完成 5确认完成',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `storeid`(`store_id`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE,
  INDEX `order_time`(`order_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for parkwash_order_queue
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_order_queue`;
CREATE TABLE `parkwash_order_queue`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NULL DEFAULT NULL COMMENT '1 入场车查询任务 2 自动确认完成任务',
  `orderid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `param_var` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '执行参数',
  `time` datetime(0) NULL DEFAULT NULL COMMENT '操作时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`type`, `orderid`) USING BTREE,
  INDEX `time`(`time`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单任务队列' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for parkwash_order_sequence
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_order_sequence`;
CREATE TABLE `parkwash_order_sequence`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `orderid` int(10) UNSIGNED NULL DEFAULT NULL,
  `uid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '标题',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`orderid`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单状态记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for parkwash_park_area
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_park_area`;
CREATE TABLE `parkwash_park_area`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `park_id` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '停车场ID',
  `floor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '楼层名称',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '区域名称',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态 1正常 0失效',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `park_id`(`park_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '停车场区域' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for parkwash_parking
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_parking`;
CREATE TABLE `parkwash_parking`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `area_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '区域ID',
  `place` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车位号',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '车位状态 1正常 0不支持洗车服务',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`area_id`, `place`) USING BTREE,
  INDEX `area_id`(`area_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '车位状态' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for parkwash_pool
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_pool`;
CREATE TABLE `parkwash_pool`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '门店ID',
  `today` date NULL DEFAULT NULL COMMENT '当天日期',
  `start_time` time(0) NULL DEFAULT NULL COMMENT '开始时间',
  `end_time` time(0) NULL DEFAULT NULL COMMENT '结束时间',
  `amount` tinyint(3) UNSIGNED NULL DEFAULT 0 COMMENT '剩号数',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `storeid`(`store_id`) USING BTREE,
  INDEX `today`(`today`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '洗车排班' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for parkwash_store
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_store`;
CREATE TABLE `parkwash_store`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `adcode` mediumint(5) NULL DEFAULT 0 COMMENT '城市代码',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '店名',
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '店面图片',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '地址',
  `tel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '电话',
  `location` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '经纬度',
  `geohash` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '经纬度geo码',
  `score` tinyint(1) UNSIGNED NULL DEFAULT 5 COMMENT '评分',
  `isdirect` tinyint(1) NULL DEFAULT 0 COMMENT '是否直营店',
  `business_hours` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '营业时间',
  `market` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '活动描述',
  `price` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '洗车最低价格（分）',
  `sort` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '排序 从大到小',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态 1正常 0建设中',
  `daily_cancel_limit` tinyint(3) UNSIGNED NULL DEFAULT 2 COMMENT '每日最多取消订单次数 0不限制 >0次数',
  `order_count` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '订单数',
  `order_count_ratio` tinyint(3) UNSIGNED NULL DEFAULT 1 COMMENT '订单数倍率',
  `time_interval` tinyint(3) UNSIGNED NULL DEFAULT 20 COMMENT '排班时段 (分钟) ',
  `time_amount` mediumint(3) UNSIGNED NULL DEFAULT 2 COMMENT '排班时段最大下单量',
  `time_day` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '排班工作日 (星期1-7)',
  `money` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '总收益（分）',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `adcode`(`adcode`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '洗车门店' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for parkwash_store_item
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_store_item`;
CREATE TABLE `parkwash_store_item`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '门店ID',
  `item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '项目ID',
  `price` mediumint(10) UNSIGNED NULL DEFAULT NULL COMMENT '价格（分）',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`store_id`, `item_id`) USING BTREE,
  INDEX `store_id`(`store_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for parkwash_trades
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_trades`;
CREATE TABLE `parkwash_trades`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `mark` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '运算符号 + 充值 - 消费',
  `money` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '变动金额 (分)',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '说明',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户资金交易记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for parkwash_usercount
-- ----------------------------
DROP TABLE IF EXISTS `parkwash_usercount`;
CREATE TABLE `parkwash_usercount`  (
  `uid` mediumint(8) UNSIGNED NOT NULL,
  `money` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '余额 (分)',
  `coupon_consume` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '已消费代金券 (分)',
  `integral` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '积分',
  `parkwash_count` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '停车场洗车次数',
  `parkwash_consume` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '停车场洗车消费金额 (分)',
  `xiche_count` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '自助洗车次数',
  `xiche_consume` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '自助洗车消费金额 (分)',
  `parkwash_firstorder` tinyint(1) NULL DEFAULT 1 COMMENT '停车场洗车首单',
  `vip_expire` datetime(0) NULL DEFAULT NULL COMMENT 'vip到期时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for pro_config
-- ----------------------------
DROP TABLE IF EXISTS `pro_config`;
CREATE TABLE `pro_config`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '数据源',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '配置名',
  `type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '数据类型',
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '配置值',
  `min` int(11) NULL DEFAULT NULL COMMENT '最小值',
  `max` int(11) NULL DEFAULT NULL COMMENT '最大值',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '说明',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统配置项' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of pro_config
-- ----------------------------
INSERT INTO `pro_config` VALUES (1, 'xc', 'admin', 'textarea', '15023767336', NULL, NULL, '管理员列表，一行一个手机号');
INSERT INTO `pro_config` VALUES (2, 'xc', 'apikey', 'text', '64BCD13B69924837B6DF728F685A05B8', NULL, NULL, '洗车机apikey');
INSERT INTO `pro_config` VALUES (3, 'xc', 'schedule_days', 'number', '3', 3, 30, '排班天数');
INSERT INTO `pro_config` VALUES (4, 'xc', 'wash_order_expire', 'number', '300', 300, 3600, '停车场洗车未支付订单超时时间 (秒)');
INSERT INTO `pro_config` VALUES (6, 'xc', 'carport_count', 'number', '5', 1, 10, '每个用户最多添加车辆的数量');
INSERT INTO `pro_config` VALUES (7, 'xc', 'user_day_order_limit', 'number', '0', 0, 10, '每个用户每天下单数量限制 (0为不限制)');
INSERT INTO `pro_config` VALUES (8, 'xc', 'wash_order_first_free', 'bool', '1', NULL, NULL, '是否开启停车场洗车首单免费活动');
INSERT INTO `pro_config` VALUES (9, 'xc', 'cancel_order_mintime', 'number', '300', 0, 86400, '距预约时间多久将不能取消订单 (秒)');
INSERT INTO `pro_config` VALUES (10, 'xc', 'vip_order_limit', 'number', '10', 1, 100, 'vip会员单天内下单数量限制');

-- ----------------------------
-- Table structure for pro_hashcheck
-- ----------------------------
DROP TABLE IF EXISTS `pro_hashcheck`;
CREATE TABLE `pro_hashcheck`  (
  `hash` char(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '唯一标识',
  `dateline` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`hash`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '验证唯一性记录表' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for pro_loginbinding
-- ----------------------------
DROP TABLE IF EXISTS `pro_loginbinding`;
CREATE TABLE `pro_loginbinding`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform` tinyint(3) UNSIGNED NULL DEFAULT NULL COMMENT '第三方平台代码',
  `uid` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '车秘用户ID',
  `type` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '绑定类型wx,qq等',
  `authcode` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '平台唯一授权码',
  `nickname` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '昵称',
  `tel` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '手机号',
  `activetime` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`platform`, `authcode`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE,
  INDEX `authcode`(`authcode`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '第三方平台绑定' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of pro_loginbinding
-- ----------------------------
INSERT INTO `pro_loginbinding` VALUES (1, 3, 61423, 'xc', '61423', '', '18926475276', '2019-04-25 11:22:24');
INSERT INTO `pro_loginbinding` VALUES (2, 3, 61427, 'xc', '61427', '', '15023767336', '2019-04-25 11:22:24');
INSERT INTO `pro_loginbinding` VALUES (3, 3, 61433, 'xc', '61433', '', '17621094331', '2019-04-25 11:25:57');
INSERT INTO `pro_loginbinding` VALUES (4, 3, 58274, 'xc', '58274', '', '15985107027', '2019-04-28 09:28:43');
INSERT INTO `pro_loginbinding` VALUES (5, 3, 61430, 'xc', '61430', '', '13511989494', '2019-04-28 10:21:21');
INSERT INTO `pro_loginbinding` VALUES (6, 3, 61428, 'xc', '61428', '', '18300873822', '2019-04-28 10:53:33');
INSERT INTO `pro_loginbinding` VALUES (7, 3, 60351, 'xc', '60351', '', '18798799483', '2019-04-28 17:51:48');

-- ----------------------------
-- Table structure for pro_payments
-- ----------------------------
DROP TABLE IF EXISTS `pro_payments`;
CREATE TABLE `pro_payments`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '交易场景',
  `trade_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '扩展字段 用户编号',
  `order_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '扩展字段 订单编号',
  `param_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '扩展字段 设备编号',
  `param_a` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '扩展字段 设备启动时间',
  `param_b` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '扩展字段 设备停止时间',
  `voucher_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '优惠券ID',
  `form_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '小程序模板消息 (表单提交场景下，为 submit 事件带上的 formId；支付场景下，为本次支付的 prepay_id)',
  `pay` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '支付金额分',
  `money` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '订单金额分',
  `payway` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '付款方式',
  `ordercode` char(28) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `paytime` datetime(0) NULL DEFAULT NULL COMMENT '支付时间',
  `mchid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '商户号',
  `trade_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '支付类型',
  `trade_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '交易号',
  `trade_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '交易状态',
  `refundcode` varchar(28) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '退款单号',
  `refundpay` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '退款金额分',
  `refundtime` datetime(0) NULL DEFAULT NULL COMMENT '退款时间',
  `uses` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用途',
  `createtime` datetime(0) NULL DEFAULT NULL COMMENT '下单时间',
  `mark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '描述字段',
  `status` tinyint(1) NULL DEFAULT 0 COMMENT '-2退款中 -1已退款 0未支付 1支付成功',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `ordercode`(`ordercode`) USING BTREE,
  INDEX `trade_id`(`trade_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for pro_platform
-- ----------------------------
DROP TABLE IF EXISTS `pro_platform`;
CREATE TABLE `pro_platform`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pfcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '平台代码',
  `aes_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Aes加密key',
  `aes_iv` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Aes加密iv',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '描述',
  `status` tinyint(3) UNSIGNED NULL DEFAULT 1 COMMENT '状态0未启用1已启用',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of pro_platform
-- ----------------------------
INSERT INTO `pro_platform` VALUES (1, '1', '0zcvnI48RbXvnmmn+1wCwOabNOlzjVgy04DIxIhiDCY=', 'K77uZoIHxeDBMp0Rjdn6Mg==', 'shop', 1);
INSERT INTO `pro_platform` VALUES (2, '2', 'rBtgc6dHk4xyfEESx5qJVFLkyRa59pUJgBOdme7OSJI=', 's1Fs6VwMuzJaiQ6WalUpSg==', 'shop', 1);

-- ----------------------------
-- Table structure for pro_ratelimit
-- ----------------------------
DROP TABLE IF EXISTS `pro_ratelimit`;
CREATE TABLE `pro_ratelimit`  (
  `skey` char(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '唯一key',
  `min_num` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '分钟访问次数',
  `hour_num` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '小时访问次数',
  `day_num` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '天访问次数',
  `time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '访问时间',
  `microtime` mediumint(3) UNSIGNED NULL DEFAULT NULL COMMENT '毫秒',
  `version` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '版本号',
  PRIMARY KEY (`skey`) USING HASH
) ENGINE = MEMORY CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '访问限流控制' ROW_FORMAT = Fixed STORAGE MEMORY;

-- ----------------------------
-- Records of pro_ratelimit
-- ----------------------------
INSERT INTO `pro_ratelimit` VALUES ('366476c3e19ca43262bba15787fa4cfe', 2, 5, 5, 1556503182, 185, 4);
INSERT INTO `pro_ratelimit` VALUES ('7db0dd38397803133fecc8cb9138b353', 1, 1, 1, 1556501816, 261, 0);
INSERT INTO `pro_ratelimit` VALUES ('0bbcb24cd61a176d7338aeb18fb66442', 3, 5, 5, 1556501834, 344, 4);
INSERT INTO `pro_ratelimit` VALUES ('44ba489343165e986ce623ba11b1d633', 2, 2, 4, 1556503955, 81, 3);
INSERT INTO `pro_ratelimit` VALUES ('9f6ef17cf36c4b0d362d453dd782d38b', 2, 2, 3, 1556503955, 65, 2);
INSERT INTO `pro_ratelimit` VALUES ('38ca00cdbfb65278a8055ff659386cc7', 1, 1, 10, 1556506407, 678, 9);
INSERT INTO `pro_ratelimit` VALUES ('809c9f0a6e99ad65f2c1d58454a476c3', 1, 3, 3, 1556501929, 936, 2);
INSERT INTO `pro_ratelimit` VALUES ('479d65b34b3dc293db3830713a10d8d1', 2, 2, 2, 1556500460, 688, 1);
INSERT INTO `pro_ratelimit` VALUES ('bf31d019ce164937bcc0ffef0940b569', 1, 1, 11, 1556506398, 467, 10);
INSERT INTO `pro_ratelimit` VALUES ('eab3550049f9fae41407cf4fb1274856', 1, 1, 7, 1556506396, 120, 6);
INSERT INTO `pro_ratelimit` VALUES ('bdc2f2609544470432346e3f7f81ca5e', 1, 6, 6, 1556501898, 835, 5);
INSERT INTO `pro_ratelimit` VALUES ('bebfa2039a13247e15f985885e9872b5', 1, 1, 10, 1556506415, 315, 9);
INSERT INTO `pro_ratelimit` VALUES ('26fc6a65e41f8dabe57cd4f0e02ed7e8', 1, 3, 3, 1556501897, 145, 2);
INSERT INTO `pro_ratelimit` VALUES ('ad62591b89e890235f289f3feb7054be', 1, 5, 5, 1556501880, 50, 4);
INSERT INTO `pro_ratelimit` VALUES ('b3a7db304b55592aeb61a8077b2a4caa', 1, 11, 11, 1556501898, 824, 10);
INSERT INTO `pro_ratelimit` VALUES ('f1999d5a8f30b0fbdbaa6867ae6ad540', 1, 5, 5, 1556501880, 58, 4);
INSERT INTO `pro_ratelimit` VALUES ('64b0117250d1d2afca8736c881dd9ee0', 1, 1, 16, 1556506933, 374, 15);
INSERT INTO `pro_ratelimit` VALUES ('baea011e16a9dc3b837cec34198b5a0f', 1, 4, 4, 1556501463, 32, 3);
INSERT INTO `pro_ratelimit` VALUES ('353045a358b57e49355f0389ce9bfd18', 1, 5, 5, 1556501462, 585, 4);
INSERT INTO `pro_ratelimit` VALUES ('6c03620233ff9e410580fbca43f55a96', 1, 1, 12, 1556503955, 162, 11);
INSERT INTO `pro_ratelimit` VALUES ('e622bb84e18e4df3624b92ed99d1fd6e', 3, 7, 7, 1556501257, 784, 6);
INSERT INTO `pro_ratelimit` VALUES ('d2e81b81b229361487acbea5d2a8ad50', 1, 1, 8, 1556506394, 489, 7);
INSERT INTO `pro_ratelimit` VALUES ('1252f349a2dfce2c3146679f41e759b5', 1, 2, 2, 1556502482, 69, 1);
INSERT INTO `pro_ratelimit` VALUES ('700416460b931abe14ae68ebc1f32697', 1, 15, 15, 1556502986, 824, 14);
INSERT INTO `pro_ratelimit` VALUES ('bb4b3ef914a4b9c141bc206bf2614aa2', 2, 3, 33, 1556506933, 902, 32);
INSERT INTO `pro_ratelimit` VALUES ('94249ada222419bf967e349ffa8ea809', 2, 2, 26, 1556506406, 105, 25);
INSERT INTO `pro_ratelimit` VALUES ('7445a1d1bc78691488f5747aa27f149d', 2, 7, 210, 1556506935, 312, 209);
INSERT INTO `pro_ratelimit` VALUES ('a7f497097411988fdc66f4da82f028b8', 3, 3, 124, 1556506827, 867, 123);
INSERT INTO `pro_ratelimit` VALUES ('bcafb064ed19c2983272cfc9dc1ad996', 3, 3, 127, 1556506827, 940, 126);
INSERT INTO `pro_ratelimit` VALUES ('0c2a9fa4e3f349412dffceb8526fac79', 1, 1, 15, 1556506918, 871, 14);
INSERT INTO `pro_ratelimit` VALUES ('6de8c8b94a6472eda2f42c7a9d60d6f5', 1, 2, 17, 1556506921, 749, 16);
INSERT INTO `pro_ratelimit` VALUES ('e25427d6a1bff3bc64eac227fa6681f6', 2, 5, 165, 1556506922, 543, 164);
INSERT INTO `pro_ratelimit` VALUES ('a230fd590a6e176a43017ae877f84f02', 1, 1, 1, 1556493997, 23, 0);
INSERT INTO `pro_ratelimit` VALUES ('98e305192e4e2dbec7c8efcb177d0a89', 1, 1, 1, 1556493996, 992, 0);
INSERT INTO `pro_ratelimit` VALUES ('c558f8f6219cd5b1cfbb2435a0d34a95', 1, 1, 1, 1556493996, 985, 0);
INSERT INTO `pro_ratelimit` VALUES ('3d05975128e7f6b4351833ee4d554662', 1, 1, 1, 1556493996, 965, 0);
INSERT INTO `pro_ratelimit` VALUES ('d8511270070e150c101de19593cc59c8', 2, 2, 2, 1556493997, 15, 1);
INSERT INTO `pro_ratelimit` VALUES ('a905b67216a77b12e2c63a15189f6421', 3, 3, 3, 1556494003, 147, 2);
INSERT INTO `pro_ratelimit` VALUES ('e54e5e1b58d859fdeadb87bd1e1693a2', 3, 3, 3, 1556494003, 207, 2);
INSERT INTO `pro_ratelimit` VALUES ('ffc3b6b6e18b6b7a3c65a63492c40ce3', 1, 1, 1, 1556493947, 606, 0);
INSERT INTO `pro_ratelimit` VALUES ('83b054d26be9f5554ed35bbf872119b6', 1, 1, 1, 1556493946, 572, 0);
INSERT INTO `pro_ratelimit` VALUES ('d75aebd18d7bd0b7155d892a18e5253f', 2, 4, 4, 1556494003, 209, 3);

-- ----------------------------
-- Table structure for pro_session
-- ----------------------------
DROP TABLE IF EXISTS `pro_session`;
CREATE TABLE `pro_session`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` mediumint(8) UNSIGNED NOT NULL,
  `scode` char(13) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `clienttype` char(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `clientapp` char(7) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `stoken` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `clientinfo` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `online` tinyint(1) NULL DEFAULT 1,
  `loginip` char(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `u`(`userid`, `clienttype`) USING BTREE,
  INDEX `u1`(`userid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 178 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of pro_session
-- ----------------------------
INSERT INTO `pro_session` VALUES (1, 61427, '5cc666dd81846', 'pc', NULL, NULL, NULL, 1, '1.204.114.159', '2019-04-29 10:52:13');
INSERT INTO `pro_session` VALUES (2, 61423, '5cc576ef7af0a', 'mp', NULL, NULL, NULL, 1, '1.204.114.159', '2019-04-28 17:48:31');
INSERT INTO `pro_session` VALUES (3, 61427, '5cc6692a11f07', 'mp', NULL, NULL, NULL, 1, '106.109.13.130', '2019-04-29 11:02:01');
INSERT INTO `pro_session` VALUES (4, 61433, '5cc653d6c94e4', 'mp', NULL, NULL, NULL, 1, '1.204.114.159', '2019-04-29 09:31:02');
INSERT INTO `pro_session` VALUES (62, 48225, '5cc65d042a480', 'pc', NULL, NULL, NULL, 1, '192.168.1.164', '2019-04-29 10:10:11');
INSERT INTO `pro_session` VALUES (105, 48225, '5cc54a6f3c936', 'wx', NULL, NULL, NULL, 1, '223.104.96.121', '2019-04-28 14:38:39');
INSERT INTO `pro_session` VALUES (109, 61427, '5cc4fc4f92705', 'mobile', NULL, NULL, NULL, 1, '106.109.0.132', '2019-04-28 09:05:19');
INSERT INTO `pro_session` VALUES (113, 58274, '5cc549b36c15d', 'mp', NULL, NULL, NULL, 1, '223.104.95.191', '2019-04-28 14:35:31');
INSERT INTO `pro_session` VALUES (128, 61430, '5cc54b8b25a51', 'mp', NULL, NULL, NULL, 1, '223.104.95.187', '2019-04-28 14:43:22');
INSERT INTO `pro_session` VALUES (133, 61428, '5cc515ae1763b', 'mp', NULL, NULL, NULL, 1, '1.204.114.159', '2019-04-28 10:53:33');
INSERT INTO `pro_session` VALUES (144, 61427, '5cc545e812ea2', 'wx', NULL, NULL, NULL, 1, '106.108.64.97', '2019-04-28 14:19:20');
INSERT INTO `pro_session` VALUES (147, 60351, '5cc577b484558', 'mp', NULL, NULL, NULL, 1, '221.13.1.26', '2019-04-28 17:51:48');

-- ----------------------------
-- Table structure for pro_smscode
-- ----------------------------
DROP TABLE IF EXISTS `pro_smscode`;
CREATE TABLE `pro_smscode`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tel` char(11) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '手机号',
  `code` char(6) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '验证码',
  `sendtime` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '发送时间',
  `errorcount` tinyint(2) UNSIGNED NULL DEFAULT 0 COMMENT '错误次数',
  `hour_fc` tinyint(2) UNSIGNED NULL DEFAULT 0 COMMENT '时级限制',
  `day_fc` tinyint(2) UNSIGNED NULL DEFAULT 0 COMMENT '天级限制',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `tel`(`tel`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '短信验证码' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of pro_smscode
-- ----------------------------
INSERT INTO `pro_smscode` VALUES (1, '15023767336', '295655', 1556432347, 1, 1, 1);

-- ----------------------------
-- Table structure for pro_trades
-- ----------------------------
DROP TABLE IF EXISTS `pro_trades`;
CREATE TABLE `pro_trades`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform` tinyint(3) UNSIGNED NULL DEFAULT NULL COMMENT '平台代码',
  `trade_no` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '交易号',
  `money` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '交易金额分',
  `type` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '1:充值 2:消费',
  `uid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `createtime` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `trade_no`(`platform`, `trade_no`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '交易记录表' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for pro_xiche_device
-- ----------------------------
DROP TABLE IF EXISTS `pro_xiche_device`;
CREATE TABLE `pro_xiche_device`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `adcode` mediumint(5) UNSIGNED NULL DEFAULT 0 COMMENT '城市代码',
  `location` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '经纬度',
  `geohash` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '经纬度geo码',
  `site` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '场地,用于地图显示合并场地',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '地址',
  `order_count` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '订单数',
  `money` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '总收益 (分)',
  `devcode` char(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '设备编码',
  `isonline` tinyint(1) NULL DEFAULT NULL COMMENT '0-离线;1-在线',
  `usestate` tinyint(1) NULL DEFAULT NULL COMMENT '0:空闲;1:投币洗车;2:刷卡洗车;3:微信洗车;4:停售;5:手机号洗车;6:会员扫码洗车; 7:缺泡沫',
  `usetime` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '设备开始使用时间戳',
  `areaid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '区块唯一ID',
  `areaname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '区块名称',
  `price` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '价格：分',
  `parameters` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '设备参数',
  `created_at` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `sort` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '排序 从大到小',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `devcode`(`devcode`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '洗车机设备信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of pro_xiche_device
-- ----------------------------
INSERT INTO `pro_xiche_device` VALUES (1, 520100, '106.643940,26.635290', 'wkezd8z9zcvj', 'A区', '贵阳市会展城', 2, 200, 'F52700B503D9BB', 1, 0, 71, '20160401114150', '贵阳1111', 100, '{\"AreaID\":20160401114150,\"AreaName\":\"贵阳\",\"Price\":1,\"Channel1\":1800,\"Channel2\":1800,\"Channel3\":1800,\"Channel4\":1800,\"Channel5\":1800,\"MaxPauseTime\":10,\"WashTotal\":30,\"NotPauseTime\":2,\"FromTime\":\"18:30\",\"ToTime\":\"00:57\",\"VXPrice\":10}', '2018-12-19 16:22:44', '2019-04-28 14:19:27', 0);
INSERT INTO `pro_xiche_device` VALUES (2, 520100, '106.622178,26.674343', 'wkezdpzvkcfm', 'B区', '贵阳市1', 1, 10, 'F527009C072522', 1, 0, 0, '20160401114150', '贵阳', 100, '{\"AreaID\":20160401114150,\"AreaName\":\"贵阳\",\"Price\":1,\"Channel1\":1800,\"Channel2\":1800,\"Channel3\":1800,\"Channel4\":1800,\"Channel5\":1800,\"MaxPauseTime\":10,\"WashTotal\":30,\"NotPauseTime\":2,\"FromTime\":\"18:30\",\"ToTime\":\"00:57\",\"VXPrice\":10}', '2018-12-20 14:47:37', '2019-04-18 16:24:03', 0);
INSERT INTO `pro_xiche_device` VALUES (3, 520100, '106.618780,26.641560', 'wkezd1vqmdsx', 'C区', '贵阳市观山湖', 0, 0, 'F52700A408497E', 1, 0, 0, '20160401114150', '贵阳', 100, '{\"AreaID\":20160401114150,\"AreaName\":\"贵阳\",\"Price\":1,\"Channel1\":1800,\"Channel2\":1800,\"Channel3\":1800,\"Channel4\":1800,\"Channel5\":1800,\"MaxPauseTime\":10,\"WashTotal\":30,\"NotPauseTime\":2,\"FromTime\":\"18:30\",\"ToTime\":\"00:57\",\"VXPrice\":10}', '2019-01-16 16:14:53', '2019-04-18 16:24:00', 0);
INSERT INTO `pro_xiche_device` VALUES (4, 520100, '106.796978,26.605943', 'wkezq5rnvgq8', 'B区', ' 贵阳市', 0, 0, 'F527007504ADD3', 0, 0, 17, '20160401114150', '深圳4', 100, '{\"AreaID\":20160401114150,\"AreaName\":\"贵阳\",\"Price\":1,\"Channel1\":1800,\"Channel2\":1800,\"Channel3\":1800,\"Channel4\":1800,\"Channel5\":1800,\"MaxPauseTime\":10,\"WashTotal\":30,\"NotPauseTime\":2,\"FromTime\":\"18:30\",\"ToTime\":\"00:57\",\"VXPrice\":10}', '2019-01-16 16:15:55', '2019-04-16 09:29:10', 0);
INSERT INTO `pro_xiche_device` VALUES (5, 520100, '106.546278,26.659643', 'wkez8t0xsjxj', 'C区', '123', 0, 0, 'F52700B1058AA7', 0, 0, 0, '20160401114150', '贵阳', 100, '{\"AreaID\":20160401114150,\"AreaName\":\"贵阳\",\"Price\":1,\"Channel1\":1800,\"Channel2\":1800,\"Channel3\":1800,\"Channel4\":1800,\"Channel5\":1800,\"MaxPauseTime\":10,\"WashTotal\":30,\"NotPauseTime\":2,\"FromTime\":\"18:30\",\"ToTime\":\"00:57\",\"VXPrice\":10}', '2019-01-18 10:56:10', '2019-04-16 09:29:13', 0);
INSERT INTO `pro_xiche_device` VALUES (6, 520100, '106.649778,26.663843', 'wkezdy5bpuw6', 'C区', 'bbb', 2, 200, 'F52700B1058AAA', 1, 0, 239, '20160401114149', '深圳123', 100, '{\"AreaID\":20160401114149,\"AreaName\":\"深圳\",\"Price\":1,\"Channel1\":1800,\"Channel2\":500,\"Channel3\":720,\"Channel4\":600,\"Channel5\":800,\"MaxPauseTime\":10,\"WashTotal\":30,\"NotPauseTime\":2,\"FromTime\":\"17:50\",\"ToTime\":\"22:30\",\"VXPrice\":10}', '2019-04-14 16:04:27', '2019-04-24 16:19:11', 0);

-- ----------------------------
-- Table structure for pro_xiche_log
-- ----------------------------
DROP TABLE IF EXISTS `pro_xiche_log`;
CREATE TABLE `pro_xiche_log`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '日志级别',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '日志名称',
  `uid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '操作用户',
  `orderno` char(27) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `devcode` char(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '设备编号',
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '日志详情',
  `created_at` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `devcode`(`devcode`) USING BTREE,
  INDEX `orderno`(`orderno`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of pro_xiche_log
-- ----------------------------
INSERT INTO `pro_xiche_log` VALUES (1, 'COrder', '用户支付1元,保存订单到洗车机异常', 61427, '201904281419277768672972657', 'F52700B503D9BB', '{\"trade\":{\"id\":71,\"trade_id\":61427,\"param_id\":1,\"param_a\":null,\"pay\":100,\"money\":100,\"ordercode\":\"201904281419277768672972657\",\"payway\":\"cbpay\"},\"result\":{\"errorcode\":-1,\"errNo\":-1,\"message\":\"\",\"result\":{\"url\":\"http:\\/\\/xicheba.net\\/chemi\\/API\\/Handler\\/COrder\",\"post\":{\"apiKey\":\"64BCD13B69924837B6DF728F685A05B8\",\"DevCode\":\"F52700B503D9BB\",\"OrderNo\":\"201904281419277768672972657\",\"totalFee\":100},\"result\":\"此设备不在线！\"}}}', '2019-04-28 14:19:27', NULL);
INSERT INTO `pro_xiche_log` VALUES (2, 'ReportStatus', '洗车机状态上报异常(ReportStatus)', NULL, NULL, NULL, '{\"get\":[],\"post\":[],\"result\":{\"errorcode\":-1,\"errNo\":-1,\"message\":\"apikey错误\",\"result\":[]}}', '2019-04-29 09:29:25', NULL);
INSERT INTO `pro_xiche_log` VALUES (3, 'ReportStatus', '洗车机状态上报异常(ReportStatus)', NULL, NULL, NULL, '{\"get\":[],\"post\":[],\"result\":{\"errorcode\":-1,\"errNo\":-1,\"message\":\"apikey错误\",\"result\":[]}}', '2019-04-29 09:29:38', NULL);

-- ----------------------------
-- Table structure for pro_xiche_login
-- ----------------------------
DROP TABLE IF EXISTS `pro_xiche_login`;
CREATE TABLE `pro_xiche_login`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '车秘用户ID',
  `type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '绑定类型wx,qq等',
  `authcode` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '平台唯一授权码',
  `openid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '微信openid',
  `nickname` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '昵称',
  `created_at` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`authcode`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '第三方平台绑定' ROW_FORMAT = Compact;

SET FOREIGN_KEY_CHECKS = 1;
