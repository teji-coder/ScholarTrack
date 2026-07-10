-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 10, 2026 at 01:33 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `scholartrack`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `scholarship_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Under Review','Waitlisted','Approved','Rejected') DEFAULT 'Pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `application_statement` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `scholarship_id`, `student_id`, `status`, `submitted_at`, `application_statement`, `admin_note`, `reviewed_at`, `reviewed_by`) VALUES
(1, 5, 3, 'Pending', '2026-07-10 10:45:48', 'I believe I should be considered for this scholarship because I am committed to my education and consistently strive to do my best academically. Receiving this scholarship would greatly help ease the financial burden of my studies, allowing me to focus more on learning and achieving my goals. I am determined to use this opportunity responsibly, continue improving myself, and give back to my community in the future through the knowledge and skills I gain.', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `application_id`, `file_name`, `uploaded_at`) VALUES
(1, 1, 'application_1_1783680348.jpg', '2026-07-10 10:45:48');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 'Your application for \"CHED Merit Scholarship Program\" was submitted successfully and is now pending review.', 0, '2026-07-10 10:45:48');

-- --------------------------------------------------------

--
-- Table structure for table `parent_information`
--

CREATE TABLE `parent_information` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `father_occupation` varchar(150) DEFAULT NULL,
  `father_company` varchar(150) DEFAULT NULL,
  `father_income` decimal(12,2) DEFAULT NULL,
  `father_contact` varchar(20) DEFAULT NULL,
  `mother_name` varchar(150) DEFAULT NULL,
  `mother_occupation` varchar(150) DEFAULT NULL,
  `mother_company` varchar(150) DEFAULT NULL,
  `mother_income` decimal(12,2) DEFAULT NULL,
  `mother_contact` varchar(20) DEFAULT NULL,
  `guardian_name` varchar(150) DEFAULT NULL,
  `guardian_relationship` varchar(100) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parent_information`
--

INSERT INTO `parent_information` (`id`, `user_id`, `father_name`, `father_occupation`, `father_company`, `father_income`, `father_contact`, `mother_name`, `mother_occupation`, `mother_company`, `mother_income`, `mother_contact`, `guardian_name`, `guardian_relationship`, `guardian_contact`) VALUES
(1, 2, 'Rosendo G. Guelas', 'na', 'na', NULL, 'na', 'Maryjane G. Guelas', 'Housewife', 'na', NULL, 'na', '', '', ''),
(2, 3, 'Jomar Pacheco', 'Manager', 'Goodwill', 35000.00, 'na', 'Ismalou Borer', 'na', 'na', NULL, 'na', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 2, 'fa7b0846c7f05136a18431b572d4e606325208f67e1bae79198db53349b51b9b', '2026-07-10 10:30:45', '2026-07-10 15:30:55', '2026-07-10 07:30:45'),
(2, 2, '559c95a603259ed66a9c8d3074d95e0f4db6b2e8eb29a4bf41e983b4a693d409', '2026-07-10 10:30:55', NULL, '2026-07-10 07:30:55'),
(3, 3, 'e683e7dedee3daf07870f16cf6bb8b8b57b674bf4ca51755546296aab53d64d1', '2026-07-10 13:20:31', '2026-07-10 18:20:53', '2026-07-10 10:20:31');

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

CREATE TABLE `scholarships` (
  `id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `provider` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `eligible_courses` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `coverage` text DEFAULT NULL,
  `monthly_allowance` decimal(12,2) DEFAULT NULL,
  `tuition_fee` decimal(12,2) DEFAULT NULL,
  `book_allowance` decimal(12,2) DEFAULT NULL,
  `transportation_allowance` decimal(12,2) DEFAULT NULL,
  `living_allowance` decimal(12,2) DEFAULT NULL,
  `one_time_grant` decimal(12,2) DEFAULT NULL,
  `minimum_gwa` decimal(3,2) DEFAULT NULL,
  `max_income` decimal(12,2) DEFAULT NULL,
  `qualifications` text DEFAULT NULL,
  `documentary_requirements` text DEFAULT NULL,
  `selection_process` text DEFAULT NULL,
  `official_website` varchar(255) DEFAULT NULL,
  `source_note` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scholarships`
--

INSERT INTO `scholarships` (`id`, `title`, `provider`, `category`, `region`, `eligible_courses`, `description`, `benefits`, `coverage`, `monthly_allowance`, `tuition_fee`, `book_allowance`, `transportation_allowance`, `living_allowance`, `one_time_grant`, `minimum_gwa`, `max_income`, `qualifications`, `documentary_requirements`, `selection_process`, `official_website`, `source_note`, `deadline`, `created_at`) VALUES
(1, 'Academic Excellence Scholarship', 'FEU', NULL, NULL, NULL, 'Full Tuition Fee', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.75, 300000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 03:20:51'),
(2, 'DOST-SEI Scholarship', 'DOST', NULL, NULL, NULL, 'Monthly stipend and allowance', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 03:20:51'),
(3, 'CHED Merit Scholarship', 'CHED', NULL, NULL, NULL, 'Government Scholarship', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.25, 500000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 03:20:51'),
(4, 'DOST-SEI Undergraduate Scholarship', 'Department of Science and Technology - Science Education Institute', NULL, NULL, NULL, 'Science and technology scholarship support. Demo listing; verify the official requirements and deadline before publishing.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.75, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(5, 'CHED Merit Scholarship Program', 'Commission on Higher Education', NULL, NULL, NULL, 'Merit-based educational assistance for qualified students. Demo listing; verify current guidelines.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.75, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(6, 'UniFAST Tertiary Education Subsidy', 'Unified Student Financial Assistance System for Tertiary Education', NULL, NULL, NULL, 'Financial assistance for eligible tertiary students. Demo listing; verify official eligibility and schedule.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.50, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(7, 'OWWA Education for Development Scholarship Program', 'Overseas Workers Welfare Administration', NULL, NULL, NULL, 'Scholarship support for qualified dependents of active OWWA members. Demo listing; verify current rules.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 500000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(8, 'OWWA OFW Dependent Scholarship Program', 'Overseas Workers Welfare Administration', NULL, NULL, NULL, 'Educational assistance for qualified OFW dependents. Demo listing; verify current rules.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.50, 500000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(9, 'SM Foundation College Scholarship', 'SM Foundation', NULL, NULL, NULL, 'College scholarship support for qualified students from low-income families. Demo listing; verify official requirements.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 350000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(10, 'Megaworld Foundation Scholarship', 'Megaworld Foundation', NULL, NULL, NULL, 'Scholarship support for qualified college students in partner schools and priority programs. Demo listing; verify official details.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(11, 'Aboitiz Foundation College Scholarship', 'Aboitiz Foundation', NULL, NULL, NULL, 'College scholarship assistance for qualified students, often focused on priority courses and partner communities. Demo listing.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(12, 'Security Bank Foundation Scholars for Better Communities', 'Security Bank Foundation', NULL, NULL, NULL, 'Scholarship support for qualified students in partner universities. Demo listing; verify current partner schools and courses.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(13, 'Gokongwei Brothers Foundation STEM Scholarship', 'Gokongwei Brothers Foundation', NULL, NULL, NULL, 'Scholarship support for students taking STEM-related degree programs. Demo listing; verify current coverage.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(14, 'Insular Foundation Gold Eagle College Scholarship', 'Insular Foundation', NULL, NULL, NULL, 'College scholarship support for qualified students in partner institutions. Demo listing; verify current guidelines.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(15, 'LANDBANK Iskolar ng LANDBANK Program', 'Land Bank of the Philippines', NULL, NULL, NULL, 'Educational support for qualified students, particularly in priority agricultural and related programs. Demo listing.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(16, 'Ayala Foundation U-Go Scholar Grant', 'Ayala Foundation', NULL, NULL, NULL, 'Scholarship support for qualified Filipino women in college. Demo listing; verify current eligibility and partner institutions.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(17, 'Cebuana Lhuillier Foundation Scholarship', 'Cebuana Lhuillier Foundation', NULL, NULL, NULL, 'Educational assistance for qualified students through selected programs and partners. Demo listing.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.25, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(18, 'JG Summit Scholarship Program', 'JG Summit Holdings', NULL, NULL, NULL, 'Scholarship support for qualified students in selected partner schools and priority degree programs. Demo listing.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.00, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(19, 'Philippine VETERANS Bank Scholarship', 'Philippine Veterans Bank', NULL, NULL, NULL, 'Educational assistance for qualified dependents or beneficiaries under selected scholarship initiatives. Demo listing.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.25, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(20, 'GSIS Educational Subsidy Program', 'Government Service Insurance System', NULL, NULL, NULL, 'Educational subsidy for qualified dependents of eligible GSIS members. Demo listing; verify current application cycle.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.25, 500000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(21, 'Meralco Foundation Scholarship', 'Meralco Foundation', NULL, NULL, NULL, 'Scholarship and technical education support under selected programs. Demo listing; verify the current offering.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.25, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(22, 'BPI Foundation Pagpupugay Scholarship', 'BPI Foundation', NULL, NULL, NULL, 'Scholarship support for eligible dependents of medical frontliners under the program\'s criteria. Demo listing.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.25, 500000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(23, 'Jollibee Group Foundation Scholarship', 'Jollibee Group Foundation', NULL, NULL, NULL, 'Educational support under selected partner programs and institutions. Demo listing; verify current availability.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2.25, 400000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(24, 'PhilFrance-DOST Scholarship', 'Embassy of France in the Philippines and DOST', NULL, NULL, NULL, 'Graduate scholarship opportunity for qualified Filipino students in science and technology fields. Demo listing.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.75, 800000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00'),
(25, 'Fulbright Foreign Student Program', 'Fulbright Philippines', NULL, NULL, NULL, 'Graduate-level scholarship opportunity for qualified Filipino applicants. Demo listing; verify official requirements and timeline.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.75, 800000.00, NULL, NULL, NULL, NULL, NULL, '2026-12-31', '2026-07-10 05:22:00');

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `sex` enum('Male','Female','Prefer not to say') DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated') DEFAULT NULL,
  `nationality` varchar(50) DEFAULT 'Filipino',
  `contact_number` varchar(20) DEFAULT NULL,
  `alternate_contact` varchar(20) DEFAULT NULL,
  `house_no` varchar(100) DEFAULT NULL,
  `street` varchar(150) DEFAULT NULL,
  `barangay` varchar(150) DEFAULT NULL,
  `municipality` varchar(150) DEFAULT NULL,
  `province` varchar(150) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `school` varchar(150) DEFAULT NULL,
  `student_no` varchar(30) DEFAULT NULL,
  `course` varchar(150) DEFAULT NULL,
  `other_course` varchar(150) DEFAULT NULL,
  `year_level` varchar(30) DEFAULT NULL,
  `gwa` decimal(3,2) DEFAULT NULL,
  `annual_income` decimal(12,2) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`id`, `user_id`, `first_name`, `middle_name`, `last_name`, `birthday`, `sex`, `civil_status`, `nationality`, `contact_number`, `alternate_contact`, `house_no`, `street`, `barangay`, `municipality`, `province`, `zipcode`, `school`, `student_no`, `course`, `other_course`, `year_level`, `gwa`, `annual_income`, `profile_picture`, `created_at`) VALUES
(1, 2, 'Janyrose', 'Garcia', 'Guelas', '2006-02-17', 'Female', 'Single', 'Filipino', '09271842454', '', 'Blk 3 Lot 21', '', 'Malagasang 1-A', 'Imus', 'Cavite', '4103', 'FEU Institute of Technology', '202410260', 'BS Information Technology', '', '2nd Year', 1.75, 500000.00, 'default.png', '2026-07-10 06:43:10'),
(2, 3, 'Erinch Nichole', 'Borer', 'Pacheco', '2005-02-03', 'Female', 'Single', 'Filipino', '09215820121', '', 'na', 'P. Noval', '458', 'Manila', 'Sampaloc', '1015', 'Polytechnic University of the Philippines', '2024-07912-MN-0', 'BS Political Science', '', '2nd Year', 1.15, 350000.00, 'profile_3_1783679985.jpg', '2026-07-10 10:20:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'ScholarTrack Administrator', 'admin@scholartrack.com', '$2y$10$tYjeqtgah7divbuyPCyuAO5myJsS8vltLF7OklfvhFeBoV5g2Y6xK', 'admin', '2026-07-10 03:20:51'),
(2, 'Janyrose Garcia Guelas', 'janyxgarcia@gmail.com', '$2y$10$LDpI0JF3tBxfg8Fyocig1.617fqBGhXBwA9CM3z0azwl263NS20fK', 'student', '2026-07-10 05:14:36'),
(3, 'Erinch Nichole Borer Pacheco', 'erinchpacheco@gmail.com', '$2y$10$CHt7GQHIt98dvdeomR960O0la8n5ypmmEalAfF1ZPGIaxtsW8n1x2', 'student', '2026-07-10 10:20:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scholarship_id` (`scholarship_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `parent_information`
--
ALTER TABLE `parent_information`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parent_information`
--
ALTER TABLE `parent_information`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`),
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `parent_information`
--
ALTER TABLE `parent_information`
  ADD CONSTRAINT `parent_information_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
