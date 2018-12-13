/*
 Navicat Premium Data Transfer

 Source Server         : 120.25.65.27__3306
 Source Server Type    : MySQL
 Source Server Version : 50637
 Source Host           : 120.25.65.27:3306
 Source Schema         : chemi_api_v1

 Target Server Type    : MySQL
 Target Server Version : 50637
 File Encoding         : 65001

 Date: 13/12/2018 09:37:07
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for pro_hashcheck
-- ----------------------------

CREATE TABLE `pro_hashcheck`  (
  `hash` char(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '唯一标识',
  `dateline` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`hash`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '验证唯一性记录表' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for pro_loginbinding
-- ----------------------------

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
) ENGINE = InnoDB AUTO_INCREMENT = 32 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '第三方平台绑定' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for pro_payments
-- ----------------------------

CREATE TABLE `pro_payments`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '交易场景',
  `trade_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '扩展字段 用户编号',
  `param_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '扩展字段 设备编号',
  `param_a` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '扩展字段 设备启动时间',
  `param_b` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '扩展字段 设备停止时间',
  `pay` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '支付金额分',
  `money` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '订单金额分',
  `payway` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '付款方式',
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
  `status` tinyint(1) NULL DEFAULT 0 COMMENT '-2退款中 -1已退款 0未支付 1支付成功',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `ordercode`(`ordercode`) USING BTREE,
  INDEX `trade_id`(`trade_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 43 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for pro_platform
-- ----------------------------

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
-- Table structure for pro_session
-- ----------------------------

CREATE TABLE `pro_session`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` mediumint(8) UNSIGNED NOT NULL,
  `scode` char(13) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `clienttype` char(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `clientapp` char(7) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
  `stoken` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
  `clientinfo` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `online` tinyint(1) NULL DEFAULT 1,
  `loginip` char(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `u`(`userid`, `clienttype`) USING BTREE,
  INDEX `u1`(`userid`) USING BTREE,
  INDEX `clienttype`(`clienttype`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 152 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for pro_smscode
-- ----------------------------

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
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '短信验证码' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for pro_trades
-- ----------------------------

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
) ENGINE = MyISAM AUTO_INCREMENT = 78 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '交易记录表' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for pro_xiche_device
-- ----------------------------

CREATE TABLE `pro_xiche_device`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `devcode`(`devcode`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '洗车机设备信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pro_xiche_log
-- ----------------------------

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
) ENGINE = MyISAM AUTO_INCREMENT = 41 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pro_xiche_login
-- ----------------------------

CREATE TABLE `pro_xiche_login`  (
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
) ENGINE = InnoDB AUTO_INCREMENT = 37 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '第三方平台绑定' ROW_FORMAT = Compact;

SET FOREIGN_KEY_CHECKS = 1;
