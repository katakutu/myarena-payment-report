<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
defined('PHP_MYSQL_CRUD_NATIVE') OR define('PHP_MYSQL_CRUD_NATIVE', TRUE);
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mutasi-includes' . DIRECTORY_SEPARATOR . 'header.php');
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mutasi-includes' . DIRECTORY_SEPARATOR . 'sidebar.php');



$page = (isset($page) ? $page : 'welcome');
$page = (is_string($page) ? strtolower($page) : 'welcome');
switch (strtolower($page)) {
	# Mutasi Bank
	case 'mutasi-account-add':
		$file_included = 'mutasi/mutasi-account-add.php';
	break;
	case 'mutasi-account-edit':
		$file_included = 'mutasi/mutasi-account-edit.php';
	break;
	case 'mutasi-account-transaction':
		$file_included = 'mutasi/mutasi-account-transaction.php';
	break;
	case 'mutasi-account-transaction-condition-modal':
		$file_included = 'mutasi/mutasi-account-transaction-condition-modal.php';
	break;
	case 'mutasi-account-transaction-condition':
		$file_included = 'mutasi/mutasi-account-transaction-condition.php';
	break;
	case 'mutasi-bank-list':
		$file_included = 'mutasi/mutasi-bank-list.php';
	break;
	# Bank Report
	case 'bankreport-account-list':
		$file_included = 'report/bankreport-account-list.php';
	break;
	case 'bankreport-account-transaction':
		$file_included = 'report/bankreport-account-transaction.php';
	break;
	case 'bankreport-autodeposit-list':
		$file_included = 'report/bankreport-autodeposit-list.php';
	break;
	//--- Modal
	case 'mutasi-account-transaction-pulled-modal':
		$file_included = 'mutasi/mutasi-account-transaction-pulled-modal.php';
	break;
	# Mutasi Developer
	case 'mutasi-developer-bank-add':
	case 'mutasi-developer-bank-edit':
		$file_included = 'mutasi/mutasi-developer-bank.php';
	break;
	
	# Suksesbugil Deposit
	case 'suksesbugil-transaction-list-all':
	case 'suksesbugil-transaction-list-approved':
	case 'suksesbugil-transaction-list-waiting':
	case 'suksesbugil-transaction-list-deleted':
	case 'suksesbugil-transaction-list-already':
	case 'suksesbugil-transaction-list-failed':
	case 'suksesbugil-transaction-list':
		$file_included = 'suksesbugil/suksesbugil-transaction-list-all.php';
	break;
	case 'suksesbugil-transaction-list-deposit-modal':
		$file_included = 'suksesbugil/suksesbugil-transaction-list-deposit-modal.php';
	break;
	case 'suksesbugil-transaction-list-deposit-action':
		$file_included = 'suksesbugil/suksesbugil-transaction-list-deposit-action.php';
	break;
	case 'suksesbugil-transaction-list-deposit':
		$file_included = 'suksesbugil/suksesbugil-transaction-list-deposit.php';
	break;
	case 'suksesbugil-deposit-details-indexed':
		$file_included = 'suksesbugil/suksesbugil-deposit-details-indexed.php';
	break;
	case 'suksesbugil-deposit-details':
		$file_included = 'suksesbugil/suksesbugil-deposit-details.php';
	break;
	# SB-Mutasi Edit
	case 'suksesbugil-deposit-scheduler-index':
		$file_included = 'suksesbugil/suksesbugil-deposit-scheduler-index.php';
	break;
	case 'suksesbugil-deposit-scheduler-edit':
		$file_included = 'suksesbugil/suksesbugil-deposit-scheduler-edit.php';
	break;
	case 'suksesbugil-mutasi-bank-edit-time':
		$file_included = 'suksesbugil/suksesbugil-mutasi-bank-edit-time.php';
	break;
	
	# Mutasi Bank
	case 'mutasi-account-list':
	default:
		$file_included = 'mutasi/mutasi-account-list.php';
	break;
	
}
include($file_included);
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mutasi-includes' . DIRECTORY_SEPARATOR . 'footer.php');



