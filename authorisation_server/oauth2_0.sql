-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2020 at 05:57 PM
-- Server version: 10.4.11-MariaDB
-- PHP Version: 7.2.28

--
-- Database: `oauth2.0`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_tokens`
--

CREATE TABLE `access_tokens` (
  `access_token` varchar(100) NOT NULL,
  `token_expires` datetime NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `scope` varchar(5000) NOT NULL,
  `client_id` varchar(100) NOT NULL,
  `is_revoked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Table structure for table `authorisation_codes`
--

CREATE TABLE `authorisation_codes` (
  `authorisation_code` varchar(255) NOT NULL,
  `code_expires` datetime NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `scope` varchar(255) NOT NULL,
  `client_id` varchar(255) NOT NULL,
  `is_revoked` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `client_id` varchar(32) NOT NULL,
  `grant_types` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `redirect_uri` varchar(2083) DEFAULT NULL,
  `client_secret` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Table structure for table `message_board`
--

CREATE TABLE `message_board` (
  `userId` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `time` varchar(255) DEFAULT NULL,
  `message` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `refresh_tokens`
--

CREATE TABLE `refresh_tokens` (
  `refresh_token` varchar(255) NOT NULL,
  `token_expires` datetime NOT NULL,
  `access_tokens` varchar(255) DEFAULT NULL,
  `is_revoked` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- --------------------------------------------------------

--
-- Table structure for table `scopes`
--

CREATE TABLE `scopes` (
  `id` varchar(32) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(320) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `approved_grant_types` varchar(512) DEFAULT 'authorization_code'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


