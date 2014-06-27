--
-- Table structure for table `groupbuy_investments`
--

CREATE TABLE IF NOT EXISTS `groupbuy_investments` (
  `account_id` int(10) unsigned NOT NULL,
  `project_id` int(10) NOT NULL,
  `investment` double NOT NULL,
  `percentage` double NOT NULL,
  PRIMARY KEY (`account_id`,`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `groupbuy_procpayments`
--

CREATE TABLE IF NOT EXISTS `groupbuy_procpayments` (
  `account_id` int(10) unsigned NOT NULL,
  `payment_id` int(10) unsigned NOT NULL,
  `amount` double NOT NULL,
  `fee` double NOT NULL,
  `donation` double NOT NULL,
  `status` enum('success','error') NOT NULL,
  `last_updated` int(10) unsigned NOT NULL,
  PRIMARY KEY (`account_id`,`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `groupbuy_projects`
--

CREATE TABLE IF NOT EXISTS `groupbuy_projects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `address` varchar(64) NOT NULL,
  `investment` double NOT NULL,
  `status` enum('actv','nact') NOT NULL,
  `last_updated` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `groupbuy_recpayments`
--

CREATE TABLE IF NOT EXISTS `groupbuy_recpayments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(255) NOT NULL,
  `address` varchar(64) NOT NULL,
  `category` varchar(16) NOT NULL,
  `amount` double NOT NULL,
  `confirmations` int(10) NOT NULL,
  `blockhash` varchar(255) NOT NULL,
  `blockindex` int(10) NOT NULL,
  `blocktime` int(10) NOT NULL,
  `txid` varchar(255) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `timereceived` int(10) unsigned NOT NULL,
  `status` enum('pending','processing','paid','error') NOT NULL DEFAULT 'pending',
  `last_updated` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address` (`address`,`txid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

