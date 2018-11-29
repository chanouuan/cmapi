/*
 Navicat Premium Data Transfer

 Source Server         : 120.25.65.27_3306
 Source Server Type    : MySQL
 Source Server Version : 50637
 Source Host           : 120.25.65.27:3306
 Source Schema         : chemi_api_v1

 Target Server Type    : MySQL
 Target Server Version : 50637
 File Encoding         : 65001

 Date: 30/10/2018 14:23:59
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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
-- Records of pro_hashcheck
-- ----------------------------
INSERT INTO `pro_hashcheck` VALUES ('JEEMCVM4', 1540802467);
INSERT INTO `pro_hashcheck` VALUES ('P4LGSR27', 1540801902);

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
  `nickname` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '昵称',
  `tel` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '手机号',
  `activetime` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`platform`, `authcode`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE,
  INDEX `authcode`(`authcode`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 24 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '第三方平台绑定' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of pro_loginbinding
-- ----------------------------
INSERT INTO `pro_loginbinding` VALUES (2, 1, 2760, 'wx', '123', '', '13888888888', '2018-10-15 17:13:19');
INSERT INTO `pro_loginbinding` VALUES (3, 1, 45377, 'wx', '111', '', '13444444444', '2018-10-15 17:19:14');
INSERT INTO `pro_loginbinding` VALUES (4, 1, 45378, 'wx', '1111', '', '15208666791', '2018-10-15 17:24:55');
INSERT INTO `pro_loginbinding` VALUES (5, 2, 45379, 'wx', '9_uid', '有梦想的人', '18785002669', '2018-10-19 10:49:16');
INSERT INTO `pro_loginbinding` VALUES (6, 2, 45379, 'wx', '19_uid', '有梦想的人', '18785002669', '2018-10-19 11:22:31');
INSERT INTO `pro_loginbinding` VALUES (7, 2, 45381, 'wx', '24_uid', '钱钱钱   别跑', '13312279165', '2018-10-20 16:25:20');
INSERT INTO `pro_loginbinding` VALUES (8, 2, 45382, 'wx', '20_uid', 'merry', '18690750650', '2018-10-22 09:47:05');
INSERT INTO `pro_loginbinding` VALUES (9, 2, 45383, 'wx', '25_uid', 'Gabe', '18825209184', '2018-10-22 11:04:59');
INSERT INTO `pro_loginbinding` VALUES (10, 1, 45378, 'wx', '2222', '', '15208666791', '2018-10-23 15:21:15');
INSERT INTO `pro_loginbinding` VALUES (11, 1, 45378, 'wx', 'aaa', '', '15208666791', '2018-10-23 16:50:25');
INSERT INTO `pro_loginbinding` VALUES (12, 1, 45378, 'wx', 'bbb', '', '15208666791', '2018-10-23 16:50:51');
INSERT INTO `pro_loginbinding` VALUES (13, 1, 45378, 'wx', 'aaaa', '', '15208666791', '2018-10-23 18:04:39');
INSERT INTO `pro_loginbinding` VALUES (14, 1, 0, '', '', '', '0', NULL);
INSERT INTO `pro_loginbinding` VALUES (16, 2, 45380, 'wx', '32_uid', '童话镇', '15620882106', '2018-10-24 17:59:12');
INSERT INTO `pro_loginbinding` VALUES (17, 2, 45378, 'wx', '33_uid', 'cyq123', '15208666791', '2018-10-24 18:34:48');
INSERT INTO `pro_loginbinding` VALUES (18, 2, 45202, 'wx', '29_uid', '有梦想的人', '18785002668', '2018-10-24 21:17:33');
INSERT INTO `pro_loginbinding` VALUES (19, 2, 45382, 'wx', '30_uid', 'merry', '18690750650', '2018-10-24 21:50:30');
INSERT INTO `pro_loginbinding` VALUES (20, 2, 3050, 'wx', '34_uid', 'cbaitl', '13037883096', '2018-10-26 10:27:44');
INSERT INTO `pro_loginbinding` VALUES (21, 2, 39741, 'wx', '36_uid', 'Simba^MeowMeow....', '17785009936', '2018-10-26 16:10:51');
INSERT INTO `pro_loginbinding` VALUES (22, 2, 45384, 'wx', '37_uid', '待我长发及腰', '18521738756', '2018-10-29 12:42:24');
INSERT INTO `pro_loginbinding` VALUES (23, 2, 45196, 'wx', '41_uid', '秋辞', '15285642781', '2018-10-29 13:34:56');

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
INSERT INTO `pro_smscode` VALUES (1, '15208666791', '450361', 1590285770, 4, 2, 4);

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
) ENGINE = MyISAM AUTO_INCREMENT = 32 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '交易记录表' ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of pro_trades
-- ----------------------------
INSERT INTO `pro_trades` VALUES (1, 1, '123', 1, 2, 45378, '2018-10-19 18:38:57');
INSERT INTO `pro_trades` VALUES (2, 1, '1232', 1, 2, 45378, '2018-10-19 18:40:26');
INSERT INTO `pro_trades` VALUES (3, 1, '111', 1, 1, 45378, '2018-10-19 18:41:23');
INSERT INTO `pro_trades` VALUES (4, 2, '15399060531934', 5, 1, 45379, '2018-10-20 08:14:49');
INSERT INTO `pro_trades` VALUES (5, 2, '15401727862048', 1, 1, 45382, '2018-10-22 09:47:06');
INSERT INTO `pro_trades` VALUES (6, 1, '4', 1, 1, 45378, '2018-10-23 16:52:22');
INSERT INTO `pro_trades` VALUES (7, 1, '5', 1, 2, 45378, '2018-10-23 16:52:44');
INSERT INTO `pro_trades` VALUES (8, 2, '15403751063283', 10, 1, 45380, '2018-10-24 17:59:12');
INSERT INTO `pro_trades` VALUES (9, 2, '15403772823387', 10, 1, 45378, '2018-10-24 20:31:58');
INSERT INTO `pro_trades` VALUES (10, 2, '15403684792987', 10, 1, 45202, '2018-10-24 21:17:33');
INSERT INTO `pro_trades` VALUES (11, 2, '15403663872980', 10, 1, 45202, '2018-10-24 21:50:29');
INSERT INTO `pro_trades` VALUES (12, 2, '15403664122993', 10, 1, 45202, '2018-10-24 21:50:30');
INSERT INTO `pro_trades` VALUES (13, 2, '15403664322966', 10, 1, 45202, '2018-10-24 21:50:30');
INSERT INTO `pro_trades` VALUES (14, 2, '15403730512943', 1, 1, 45202, '2018-10-24 21:50:30');
INSERT INTO `pro_trades` VALUES (15, 2, '15403731843051', 10, 1, 45382, '2018-10-24 21:50:30');
INSERT INTO `pro_trades` VALUES (16, 2, '15403735313022', 10, 1, 45382, '2018-10-24 21:50:30');
INSERT INTO `pro_trades` VALUES (17, 2, '15403755063289', 10, 1, 45380, '2018-10-24 21:50:30');
INSERT INTO `pro_trades` VALUES (18, 2, '15403861992970', 10, 1, 45202, '2018-10-24 21:50:30');
INSERT INTO `pro_trades` VALUES (19, 1, '222', 1, 2, 45378, '2018-10-25 09:17:30');
INSERT INTO `pro_trades` VALUES (20, 2, '15403805122974', 20000, 1, 45202, '2018-10-25 10:51:26');
INSERT INTO `pro_trades` VALUES (21, 2, '15404465682919', 10, 1, 45202, '2018-10-25 13:50:12');
INSERT INTO `pro_trades` VALUES (22, 1, 'ere', 1, 1, 45378, '2018-10-26 08:56:55');
INSERT INTO `pro_trades` VALUES (23, 1, 'gfgf', 1, 2, 45378, '2018-10-26 08:57:48');
INSERT INTO `pro_trades` VALUES (24, 2, '15405222132986', 10, 1, 45202, '2018-10-26 10:50:55');
INSERT INTO `pro_trades` VALUES (25, 2, '15405222442923', 10, 1, 45202, '2018-10-26 10:54:37');
INSERT INTO `pro_trades` VALUES (26, 2, '15405226682988', 10, 1, 45202, '2018-10-26 10:58:34');
INSERT INTO `pro_trades` VALUES (27, 1, '123213', 1, 2, 45378, '2018-10-26 11:14:44');
INSERT INTO `pro_trades` VALUES (28, 1, 'wewqe', 1, 1, 45378, '2018-10-26 11:15:32');
INSERT INTO `pro_trades` VALUES (29, 2, '15405417983038', 10, 1, 45382, '2018-10-26 16:17:41');
INSERT INTO `pro_trades` VALUES (30, 2, '15408018122920', 10, 1, 45202, '2018-10-29 16:31:42');
INSERT INTO `pro_trades` VALUES (31, 2, '15408024342980', 10, 1, 45202, '2018-10-29 16:41:07');

SET FOREIGN_KEY_CHECKS = 1;
