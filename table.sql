--
-- Table structure for table `mi2_users_patients`
--

CREATE TABLE `mi2_users_patients` (
`id` bigint(20) NOT NULL,
`user_id` bigint(20) NOT NULL,
`pid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mi2_users_patients`
--
ALTER TABLE `mi2_users_patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`pid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mi2_users_patients`
--
ALTER TABLE `mi2_users_patients`
MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Table structure for table `mi2_users_supervisors`
--

CREATE TABLE `mi2_users_supervisors` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `super_user_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mi2_users_supervisors`
--
ALTER TABLE `mi2_users_supervisors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`super_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mi2_users_supervisors`
--
ALTER TABLE `mi2_users_supervisors`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Table structure for table `mi2_exclude_roles`
--

CREATE TABLE `mi2_exclude_roles` (
  `id` bigint(20) NOT NULL,
  `gid` int(11) NOT NULL COMMENT 'GACL Group ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mi2_exclude_roles`
--
ALTER TABLE `mi2_exclude_roles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mi2_exclude_roles`
--
ALTER TABLE `mi2_exclude_roles`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

