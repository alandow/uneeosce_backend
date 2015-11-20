-- MySQL dump 10.13  Distrib 5.6.24, for Win64 (x86_64)

-- ------------------------------------------------------
-- Server version	5.5.44-cll-lve

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `assessment_criteria_scale_types`
--

DROP TABLE IF EXISTS `assessment_criteria_scale_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_criteria_scale_types` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `description` text,
  `notes` text,
  `deleted` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_criteria_scale_types`
--

LOCK TABLES `assessment_criteria_scale_types` WRITE;
/*!40000 ALTER TABLE `assessment_criteria_scale_types` DISABLE KEYS */;
INSERT INTO `assessment_criteria_scale_types` VALUES (1,'JMP eOSCE Binary','The marking criteria used in most classic JMP eOSCE scenarios',NULL),(2,'Tollefson 3 Point','3 point scale- Competent, Requires Supervision, Requires Development',NULL),(3,'USW Pontyprydd scale','4 point scale used by University of South Wales, Pontyprydd',NULL),(4,'Bondy 5 point','5-point scale defined by Bondy (1983)',NULL);
/*!40000 ALTER TABLE `assessment_criteria_scale_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_criteria_scales_items`
--

DROP TABLE IF EXISTS `assessment_criteria_scales_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_criteria_scales_items` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_criteria_scale_typeID` int(11) DEFAULT NULL,
  `long_description` text,
  `short_description` varchar(45) DEFAULT NULL,
  `value` varchar(45) DEFAULT NULL,
  `needs_comment` varchar(45) DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  `deleted` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_criteria_scales_items`
--

LOCK TABLES `assessment_criteria_scales_items` WRITE;
/*!40000 ALTER TABLE `assessment_criteria_scales_items` DISABLE KEYS */;
INSERT INTO `assessment_criteria_scales_items` VALUES (1,1,'Satisfactory','S','1',NULL,1,NULL),(2,1,'Not Satisfactory','NS','0','true',0,NULL),(4,2,'Competent','C','2',NULL,0,NULL),(5,2,'Requires Supervision','S','1',NULL,1,NULL),(6,2,'Requires Development','D','0','true',2,NULL),(9,3,'Not done','0','0',NULL,0,NULL),(10,3,'Performed after one prompt','1','1',NULL,1,NULL),(11,3,'Done without prompts','2','2',NULL,2,NULL),(12,3,'Performed confidently/excellently with good explanation and rationale','3','3',NULL,3,NULL),(13,4,'Not observed','0','0',NULL,0,NULL),(14,4,'Dependent','1','1',NULL,1,NULL),(15,4,'Marginal','2','2',NULL,2,NULL),(16,4,'Assisted','3','3',NULL,3,NULL),(17,4,'Supervised','4','4',NULL,4,NULL),(18,4,'Independent','5','5',NULL,5,NULL);
/*!40000 ALTER TABLE `assessment_criteria_scales_items` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Table structure for table `cohort_lookup`
--

DROP TABLE IF EXISTS `cohort_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cohort_lookup` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `label` text,
  `value` text,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cohort_lookup`
--

LOCK TABLES `cohort_lookup` WRITE;
/*!40000 ALTER TABLE `cohort_lookup` DISABLE KEYS */;
INSERT INTO `cohort_lookup` VALUES (1,'MEDI1011','MEDI1011'),(2,'MEDI1012','MEDI1012');
/*!40000 ALTER TABLE `cohort_lookup` ENABLE KEYS */;
UNLOCK TABLES;



--
-- Table structure for table `dict`
--

DROP TABLE IF EXISTS `dict`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dict` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `string` text,
  `definition_en` text,
  `definition_cy` text,
  `editable` varchar(45) DEFAULT NULL,
  `deleted` varchar(45) DEFAULT 'false',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dict`
--

LOCK TABLES `dict` WRITE;
/*!40000 ALTER TABLE `dict` DISABLE KEYS */;
INSERT INTO `dict` VALUES (1,'system_users_setup','System Users Setup',NULL,'true','false'),(2,'participants_setup','Students Management',NULL,'true','false'),(3,'eosce_setup','Examination Preparation and Administration',NULL,'true','false'),(4,'logout','Log Out',NULL,'true','false'),(5,'systemname','UNE School of Rural Medicine eOSCE',NULL,'true','false'),(6,'admin_available_actions','Available Admin actions:',NULL,'true','false'),(7,'assessor_available_examinations','Examinations available to you as an Assessor:',NULL,'true','false'),(8,'user_management_label','User Management',NULL,'true','false'),(9,'system_administration_label','System Management',NULL,'true','false'),(10,'new_user','New User',NULL,'true','false'),(11,'create_user','Create User',NULL,'true','false'),(12,'update_user','Update User',NULL,'true','false'),(13,'edit_user','Edit User',NULL,'true','false'),(14,'really_delete_user','Really delete user?',NULL,'true','false'),(15,'really_delete','Really delete?',NULL,'true','false'),(16,'user_full_name','User Full Name',NULL,'true','false'),(17,'user_role','User Role',NULL,'true','false'),(19,'user_password','Password',NULL,'true','false'),(20,'userpage_instructions','Click the \'New User\' button to add a user',NULL,'true','false'),(21,'userlist_legend','Users: click username to edit',NULL,'true','false'),(22,'user_login','Login',NULL,'true','false'),(23,'user_name','Name',NULL,'true','false'),(24,'user_searchbut','Search',NULL,'true','false'),(25,'participantpage_title','Student Management',NULL,'true','false'),(26,'new_participant','New Student',NULL,'true','false'),(27,'create_participant','Create Student',NULL,'true','false'),(28,'update_participant','Update Student',NULL,'true','false'),(29,'really_delete_participant','Really delete student?',NULL,'true','false'),(30,'participant_details','Student Details',NULL,'true','false'),(31,'participant_num','Student ID',NULL,'true','false'),(32,'participant_fname','Student First Name',NULL,'true','false'),(33,'participant_lname','Student Last Name',NULL,'true','false'),(34,'participant_email','Student Email',NULL,'true','false'),(35,'participant_upload_image_lbl','Select student image (JPG only!)',NULL,'true','false'),(36,'participantpage_instructions','Click the \'New Student\' button to add a student<br/><br/>',NULL,'true','false'),(37,'participant_legend','Students: click student number to edit',NULL,'true','false'),(38,'participant_management_label','Manage Students',NULL,'true','false'),(39,'participant_photo','Student Photo',NULL,'true','false'),(40,'participant_student','Delete',NULL,'true','false'),(41,'osce_session_management','Manage Examinations',NULL,'true','false'),(42,'assessment_item_management','Manage Assessment Items',NULL,'true','false'),(43,'sessions_management_label','Manage Examinations',NULL,'true','false'),(44,'active_osce_sessions','Active Exam Sessions',NULL,'true','false'),(45,'osce_session_description','Description',NULL,'true','false'),(46,'osce_session_unit','Unit',NULL,'true','false'),(47,'osce_session_date','Exam Date',NULL,'true','false'),(48,'osce_session_created_on','Created On',NULL,'true','false'),(49,'osce_session_created_by','Created By',NULL,'true','false'),(50,'osce_session_assessors','Assessors',NULL,'true','false'),(51,'osce_session_participants','Students',NULL,'true','false'),(52,'osce_session_status','Status',NULL,'true','false'),(53,'participants_count','Number of students:',NULL,'true','false'),(54,'system_admin_text','Select an action from the links below<br/>',NULL,'true','false'),(55,'osce_sessions_count','Number of Examinations',NULL,'true','false'),(56,'new_osce_session','New Examination',NULL,'true','false'),(57,'create_osce_session_btn_lbl','OK',NULL,'true','false'),(58,'update_osce_session','Update Examination',NULL,'true','false'),(59,'update_osce_session_btn_lbl','OK',NULL,'true','false'),(60,'update_osce_item_definitions_btn_lbl','Item definitions...',NULL,'true','false'),(61,'really_delete_session','Really delete examination?',NULL,'true','false'),(62,'assign_students_to_session','Assign students to ',NULL,'true','false'),(63,'osce_session_details','Examination Details',NULL,'true','false'),(64,'osce_session_name','Examination name',NULL,'true','false'),(65,'osce_session_description','Examination description',NULL,'true','false'),(66,'osce_session_unit','Unit',NULL,'true','false'),(67,'osce_session_owner','Course Co-ordinator',NULL,'true','false'),(68,'osce_session_pick_assessors','Select Assessors',NULL,'true','false'),(69,'osce_session_available_assessors','Available assessors',NULL,'true','false'),(70,'osce_session_assigned_assessors','Assigned Assessors',NULL,'true','false'),(71,'osce_session_pick_participants','Pick Students',NULL,'true','false'),(72,'osce_session_available_participants','Available students',NULL,'true','false'),(73,'osce_session_assigned_participante','Assigned students',NULL,'true','false'),(74,'osce_sessions_page_text','Click the \'New Exam\' button to add an exam instance<br/><br/>',NULL,'true','false'),(75,'osce_sessions_page_legend','Examinations: click to edit content',NULL,'true','false'),(76,'edit_osce_page_label','Manage an Examination',NULL,'true','false'),(77,'assessment_item_list_type','Essential?',NULL,'true','false'),(78,'assessment_item_list_text','Text',NULL,'true','false'),(79,'assessment_item_list_remove','Remove',NULL,'true','false'),(80,'assessment_item_use_count','Use count',NULL,'true','false'),(81,'assessment_item_list_mandatory','Required?',NULL,'true','false'),(82,'edit_osce_session_finalise','Really finalise examination?<br/>This session can only be unlocked by the Course Co-ordinator if you do this',NULL,'true','false'),(83,'edit_osce_session_unfinalise','Really unlock examination?<br/>This may invalidate any assessments made in this session!',NULL,'true','false'),(84,'edit_osce_session_finalise_lbl','Finalise',NULL,'true','false'),(85,'edit_osce_session_unfinalise_lbl','Unlock for editing',NULL,'true','false'),(86,'edit_osce_add_assessment_item','Add Assessment Item',NULL,'true','false'),(87,'edit_osce_session_questions_heading','Assessment Items for this examination',NULL,'true','false'),(88,'edit_osce_search_items_legend','Search for assessment item',NULL,'true','false'),(89,'assessment_item_text','Item Text',NULL,'true','false'),(90,'assessment_item_type','Item Type',NULL,'true','false'),(91,'assessment_item_notes','Item Notes',NULL,'true','false'),(92,'assessment_item_competencies_list_header','Competencies',NULL,'true','false'),(93,'assessment_item_preview_review','Review Assessment Item',NULL,'true','false'),(94,'reports_index_label','Examination Monitoring and Reporting',NULL,'true','false'),(95,'osce_session','Examination',NULL,'true','false'),(96,'osce_session_search_date_from','Examination Date From',NULL,'true','false'),(97,'osce_session_search_date_to','Examination Date To',NULL,'true','false'),(98,'reports_detail_label','Report Detail',NULL,'true','false'),(99,'really_delete_item','Really delete item?',NULL,'true','false'),(100,'assessment_item_management','Manage Assessment Items and Competencies',NULL,'true','false'),(101,'create_assessment_item','Create Assessment Item',NULL,'true','false'),(102,'create_assessment_item_dialog_title','Create an Assessment Item',NULL,'true','false'),(103,'create_assessment_item_btn_lbl','OK',NULL,'true','false'),(105,'system_setup_help','Click here to:<ul><li>Enter and edit new administrators</li><li>Enter and edit new examiners</li><li>Enter and edit new co-ordinators</li><li>Enter and edit new year managers</li><li>Edit system labels</li></ul>',NULL,'true','false'),(106,'participants_setup_help','Click here to:<ul><li>Manually enter students that are not enrolled at UoN</li><li>Change student details</li></ul>',NULL,'true','false'),(107,'osce_setup_help','Click here to:<ul><li>Create an exam</li><li>Modify an exam</li><li>Enrol students in an exam</li><li>Link examiners to an exam</li><li>Manage assessment items</li></ul>',NULL,'true','false'),(108,'reports_help','Click here to:<ul><li>View exam results</li><li>Export exam results as Excel or PDF for review and analysis</li><li>Print student feedback</li><li>Send email feedback to students</li></ul>',NULL,'true','false'),(109,'participant_cohort','Enrolled in',NULL,'true','false'),(110,'feedback_email_text','<p>Dear Year 2 students,</p><p>Please find attached your eOSCE Feedback Form.</p><p>This form is designed to provide you with feedback on your performance of the competencies. Additionally if the competency was not performed satisfactorily, the examiner has provided comments. &nbsp;As the examiner comments are added at the bedside, please be understanding of any typing mistakes and spelling errors. It is not possible to alter comments once submitted. This is a learning support for you and is NOT designed as a Grading Sheet to reflect your OVERALL GLOBAL RATING.</p>\r\n<p>For any queries, or if you wish for specific feedback please contact your Course/Unit Coordinator</p><p><em>Dr Graeme Horton, BMed- Joint Medical Program Convenor</em></p><p><em>Dr Stuart Wark, Year 2 Coordinator  (UNE)</em></p>',NULL,'true','false'),(111,'string_management_label','Manage system labels',NULL,'true','false'),(112,'string_management_instructions','Use this interface to manage the labels in the app.<br/>To see definitions in the app, change $CFG->showstringsource to \"true\" in config.inc.php',NULL,'true','false'),(113,'string_management_form_label','System Labels',NULL,'true','false'),(114,'osce_archived_session_management','Archived Examinations',NULL,'true','false'),(115,'osce_session_archive','Archived Examinations',NULL,'true','false'),(116,'osce_archives_page_text','View archived examinations',NULL,'true','false'),(117,'osce_archives_page_legend','Archived Examinations',NULL,'true','false'),(118,'edit_osce_session_archive_lbl','Archive',NULL,'true','false'),(119,'edit_osce_session_unarchive_lbl','Un-archive This Examination',NULL,'true','false'),(120,'system_users_setup_help','Click here to:<ul><li>Enter and edit new administrators</li><li>Enter and edit new examiners</li><li>Enter and edit new co-ordinators</li><li>Enter and edit new year managers</li></ul>',NULL,'true','false'),(121,'system_labels_help','Click here to change system labels. Administrators only!',NULL,'true','false'),(122,'eosce_archive','Examination Archive',NULL,'true','false'),(123,'osce_archive_help','Click here to access archived (old) exams.',NULL,'true','false'),(124,'edit_osce_session_details','Edit Session',NULL,'true','false'),(125,'edit_osce_session_name','Session Name',NULL,'true','false'),(126,'edit_osce_session_unit','Unit',NULL,'true','false'),(127,'edit_osce_session_owner','Session Owner',NULL,'true','false'),(128,'edit_osce_session','Edit Session',NULL,'true','false'),(129,'suggested_assessment_items','Similar assessment items',NULL,'true','false'),(130,'item_type_help_string','Is this an essential criteria?<br/>Not achieving this criteria is an automatic fail of the exam',NULL,'true','false'),(131,'edit_assessment_item_btn_lbl','OK',NULL,'true','false'),(132,'edit_assessment_item','Edit Assessment Item',NULL,'true','false'),(133,'edit_osce_session_archive_confirm_msg','Really archive assessment?',NULL,'true','false'),(134,'osce_session_start_date_time','Start Date and Time',NULL,'true','false'),(135,'really_deactivate_session','Confirm Deactivation',NULL,'true','false'),(136,'really_activate_session','Confirm Activation',NULL,'true','false'),(137,'edit_osce_session_overview_heading','Overview',NULL,'true','false'),(138,'osce_session_completed_date','Completed date',NULL,'true','false'),(139,'import_osce_session','Import an Examination',NULL,'true','false'),(140,'import_exam','Import Examination',NULL,'true','false'),(141,'import_osce_session_btn_lbl','Import',NULL,'true','false'),(142,'edit_email_stem','Edit Feedback Email',NULL,'true','false'),(143,'edit_email_stem_btn_lbl','OK',NULL,'true','false'),(144,'clone_osce_session_btn_lbl','Clone Examination',NULL,'true','false'),(145,'clone_exam','Clone Examination',NULL,'true','false'),(146,'add_student_by_csv_help','Add students using a CSV file. The CSV file must contain the following headers:<ul><li><b>studentid</b> which is the UoN student ID</li><li><b>site</b> which is the short code for the site. Right now it\'s either UoN or UNE</li></ul>Please see the example for a template',NULL,'true','false'),(147,'download_report_summary_excel','Download Summary Report As Excel',NULL,'true','false'),(148,'download_report_comprehensive_excel','Download Analysis Report As Excel',NULL,'true','false'),(149,'edit_email_send_test_btn_lbl','Send Test Email',NULL,'true','false'),(150,'test_email_to','Email Address:',NULL,'true','false'),(151,'email_send_test_btn_lbl','Send Email',NULL,'true','false'),(152,'system_lookups_form_label','Lookups',NULL,'true','false'),(153,'system_lookups_help','Manage system lookup lists here',NULL,'true','false'),(154,'completed_osce_sessions','Completed Exam Sessions',NULL,'true','false'),(155,'osce_sessions','Examination Sessions',NULL,'true','false'),(156,'activate_osce','Start',NULL,'true','false'),(157,'deactivate_osce','Stop',NULL,'true','false'),(158,'activate_practice_osce','Start Practice Session',NULL,'true','false'),(159,'deactivate_practice_osce','Stop Practice Session',NULL,'true','false'),(160,'osce_session_scale','Rating Scale','Graddfa Asesu','true','false'),(161,'start_practicing','Really start practicing?','Cadarnhau ddechrau ymarfer?','true','false'),(162,'really','Really?',NULL,'true','true'),(163,'string_criteria_types_label','Assessment Scales setup','Graddfeydd Asesu a sefydlwyd','true','false'),(164,'running_examinations','Running Examinations Overview','Rhedeg Arholiadau Trosolwg','true','false');
/*!40000 ALTER TABLE `dict` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `error_log`
--

DROP TABLE IF EXISTS `error_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `error_log` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` bigint(20) DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error_log`
--

LOCK TABLES `error_log` WRITE;
/*!40000 ALTER TABLE `error_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `error_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_instances`
--

DROP TABLE IF EXISTS `exam_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_instances` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` text,
  `description` text,
  `unit_id` int(11) DEFAULT NULL,
  `scaleID` int(11) NOT NULL DEFAULT '0',
  `exam_starttimestamp` bigint(20) DEFAULT NULL,
  `exam_endtimestamp` varchar(45) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `created_timestamp` bigint(20) DEFAULT NULL,
  `modified_byID` int(11) DEFAULT NULL,
  `modified_timestamp` bigint(20) DEFAULT NULL,
  `deleted` text,
  `owner_id` int(11) DEFAULT NULL,
  `finalised` varchar(45) DEFAULT 'false',
  `active` varchar(45) DEFAULT 'false',
  `practicing` varchar(45) DEFAULT 'false',
  `emailtext` text,
  `archived` varchar(45) DEFAULT 'false',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `exam_questions`
--

DROP TABLE IF EXISTS `exam_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_questions` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) DEFAULT NULL,
  `text` text,
  `type` int(11) DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  `deleted` varchar(45) DEFAULT 'false',
  `created_timestamp` bigint(20) DEFAULT NULL,
  `modified_timestamp` bigint(20) DEFAULT NULL,
  `created_byID` int(11) DEFAULT NULL,
  `modified_byID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=198 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `mail_log`
--

DROP TABLE IF EXISTS `mail_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mail_log` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `participant_to_id` int(11) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=1442 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `recordID` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `size` varchar(50) NOT NULL,
  `data` longblob,
  `label` varchar(100) NOT NULL,
  `comments_data` text,
  `file_path` text NOT NULL,
  `thumb_path` text,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `description` text,
  `notes` text,
  `conduct_assessment` varchar(45) DEFAULT NULL,
  `view_system_users` varchar(45) DEFAULT NULL,
  `edit_system_users` varchar(45) DEFAULT NULL,
  `view_students` varchar(45) DEFAULT NULL,
  `edit_students` varchar(45) DEFAULT NULL,
  `view_assessments` varchar(45) DEFAULT NULL,
  `edit_assessments` varchar(45) DEFAULT NULL,
  `finalise_assessment` varchar(45) DEFAULT NULL,
  `finalise_other_assessment` varchar(45) DEFAULT NULL,
  `assign_students_to_assessment` varchar(45) DEFAULT NULL,
  `assign_assessors_to_assessment` varchar(45) DEFAULT NULL,
  `view_reports` varchar(45) DEFAULT NULL,
  `view_assessment_items` varchar(45) DEFAULT NULL,
  `edit_assessment_items` varchar(45) DEFAULT NULL,
  `edit_system_strings` varchar(45) DEFAULT NULL,
  `print_pdf_exam` varchar(45) DEFAULT NULL,
  `print_word_exam` varchar(45) DEFAULT NULL,
  `edit_lookups` varchar(45) DEFAULT NULL,
  `edit_system_config` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','An admin can pretty much do anything','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true'),(2,'co-ordinator','Co-ordinators can create, edit and finalise examinations, and see reports','false','false','false','false','false','true','true','true','false','true','true','true','false','false','false','true','true','false','false'),(3,'year manager','Year managers can create and edit examinations, and see reports. (but not finalise examinations)','false','false','false','false','false','true','false','false','false','true','true','true','false','false','false','true','false','false','false'),(4,'examiner','Examiners can conduct examinations only.','true','false','false','false','false','false','false','false','false','false','false','false','false','false','false','false','false','false','false');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_lookup`
--

DROP TABLE IF EXISTS `site_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_lookup` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `description` text,
  `code` text,
  `deleted` varchar(45) DEFAULT NULL,
  `editable` varchar(45) DEFAULT 'true',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_lookup`
--

LOCK TABLES `site_lookup` WRITE;
/*!40000 ALTER TABLE `site_lookup` DISABLE KEYS */;
INSERT INTO `site_lookup` VALUES (1,'UNE Armidale Campus','UNE',NULL,'true'),(2,'UoN Callaghan Campus','UoN',NULL,'true');
/*!40000 ALTER TABLE `site_lookup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_exam_instance_link`
--

DROP TABLE IF EXISTS `student_exam_instance_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_exam_instance_link` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `students_ID` int(11) DEFAULT NULL,
  `exam_instances_ID` int(11) DEFAULT NULL,
  `site_ID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_exam_sessions`
--

DROP TABLE IF EXISTS `student_exam_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_exam_sessions` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `student_ID` int(11) DEFAULT NULL,
  `form_ID` int(11) DEFAULT NULL,
  `created_timestamp` bigint(20) DEFAULT NULL,
  `created_by_ID` int(11) DEFAULT NULL,
  `start_timestamp` bigint(20) DEFAULT NULL,
  `overall_rating` int(11) DEFAULT NULL,
  `additional_rating` int(11) DEFAULT NULL,
  `comments` text,
  `signature_image` blob,
  `student_exam_sessions` varchar(45) DEFAULT NULL,
  `moderated_timestamp` bigint(20) DEFAULT NULL,
  `moderated_overall_rating` varchar(45) DEFAULT NULL,
  `moderated_additional_rating` varchar(45) DEFAULT NULL,
  `moderated_comments` text,
  `status` varchar(45) DEFAULT NULL,
  `end_timestamp` bigint(20) DEFAULT NULL,
  `moderated_by_id` int(11) DEFAULT NULL,
  `last_modified_by_ID` int(11) DEFAULT NULL,
  `last_modified_timestamp` bigint(20) DEFAULT NULL,
  `site_ID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `student_exam_sessions_changelog`
--

DROP TABLE IF EXISTS `student_exam_sessions_changelog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_exam_sessions_changelog` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `student_exam_sessions_ID` int(11) DEFAULT NULL,
  `changed_by_id` int(11) DEFAULT NULL,
  `oldrating` varchar(45) DEFAULT NULL,
  `newrating` varchar(45) DEFAULT NULL,
  `timestamp` bigint(20) DEFAULT NULL,
  `oldadditionalrating` varchar(45) DEFAULT NULL,
  `newadditionalrating` varchar(45) DEFAULT NULL,
  `oldcomments` text,
  `newcomments` text,
  `description` text,
  `type` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `student_exam_sessions_responses`
--

DROP TABLE IF EXISTS `student_exam_sessions_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_exam_sessions_responses` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `student_exam_session_ID` int(11) DEFAULT NULL,
  `question_ID` int(11) DEFAULT NULL,
  `answer` varchar(45) DEFAULT NULL,
  `comments` text,
  `created_timestamp` varchar(45) DEFAULT NULL,
  `moderated_answer` varchar(45) DEFAULT NULL,
  `moderated_comments` text,
  `moderated_by_ID` int(11) DEFAULT NULL,
  `moderated_timestamp` bigint(20) DEFAULT NULL,
  `last_modified_by_ID` int(11) DEFAULT NULL,
  `last_modified_timestamp` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=483 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `student_exam_sessions_responses_changelog`
--

DROP TABLE IF EXISTS `student_exam_sessions_responses_changelog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_exam_sessions_responses_changelog` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `changed_by_ID` int(11) DEFAULT NULL,
  `student_exam_sessions_responses_ID` int(11) DEFAULT NULL,
  `oldvalue` varchar(45) DEFAULT NULL,
  `newvalue` varchar(45) DEFAULT NULL,
  `timestamp` bigint(20) DEFAULT NULL,
  `description` text,
  `oldcomment` text,
  `newcomment` text,
  `type` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `student_images`
--

DROP TABLE IF EXISTS `student_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_images` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `data` longblob,
  `student_ID` int(11) DEFAULT NULL,
  `path` text,
  `filename` text,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `fname` text,
  `lname` varchar(45) DEFAULT NULL,
  `studentnum` varchar(45) DEFAULT NULL,
  `locked` int(11) DEFAULT '0',
  `email` text,
  `cohort` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `token`
--

DROP TABLE IF EXISTS `token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `token` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `token` text NOT NULL,
  `userID` int(11) NOT NULL,
  `info` varchar(256) NOT NULL,
  `expiry` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=758 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `unit_lookup`
--

DROP TABLE IF EXISTS `unit_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unit_lookup` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `description` text,
  `editable` varchar(45) DEFAULT 'true',
  `deleted` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit_lookup`
--

LOCK TABLES `unit_lookup` WRITE;
/*!40000 ALTER TABLE `unit_lookup` DISABLE KEYS */;
INSERT INTO `unit_lookup` VALUES (1,'MEDI1013','true',NULL),(2,'MEDI1012','true',NULL);
/*!40000 ALTER TABLE `unit_lookup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `username` text,
  `password` text,
  `name` text,
  `roleID` int(11) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `deleted` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','2c325313946813abc44cad158f9b5eef','Admin user',1,'manual',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_exam_instances_link`
--

DROP TABLE IF EXISTS `users_exam_instances_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_exam_instances_link` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `users_ID` int(11) DEFAULT NULL,
  `exam_instances_ID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-11-20 13:10:03
