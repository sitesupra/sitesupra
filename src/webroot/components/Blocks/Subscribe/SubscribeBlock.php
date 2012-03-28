<?php

namespace Project\Blocks\Subscribe;

use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\BlockController;
use Supra\Request;
use Supra\Response;
use Supra\Mailer;
use Supra\Mailer\Message;
use Supra\Mailer\Message\TwigMessage;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Uri\Path;
use Supra\Mailer\MassMail\Entity\Subscriber;
use Supra\Validator\Exception;
//use Supra\Mailer\Exception;

/**
 * Description of SubscribeBlock
 *
 * @author aleksey
 */
class SubscribeBlock extends BlockController
{
	const ACTION_SUBSCRIBE = 'subscribe';
	const ACTION_UNSUBSCRIBE = 'unsubscribe';
	const ACTION_CONFIRM_SUBSCRIBE = 'confirm_subscribe';
	const ACTION_CONFIRM_UNSUBSCRIBE = 'confirm_unsubscribe';

	/**
	 * Current request
	 * @var PageRequest
	 */
	protected $request;

	/**
	 * Current response
	 * @var Response\TwigResponse
	 */
	protected $response;
	protected $subscriberManager;
	protected $massMail;

	public function __construct()
	{
		parent::__construct();
		$this->massMail = ObjectRepository::getMassMail($this);
		$this->subscriberManager = $this->massMail->getSubscriberManager();
	}

	public function doExecute()
	{

		$this->request = $this->getRequest();
		$this->response = $this->getResponse();

		$action = $this->request->getParameter('action');

		// Selecting subscribe-action
		switch ($action) {

			case self::ACTION_CONFIRM_SUBSCRIBE: {

					$this->actionConfirmSubscribe();
				}break;
			case self::ACTION_CONFIRM_UNSUBSCRIBE: {

					$this->actionConfirmUnsubscribe();
				}break;
			case self::ACTION_UNSUBSCRIBE: {

					$this->actionUnsubscribe();
				}break;
			default : {

					$this->actionSubscribe();
				}
		}

		// Local file is used
		$this->response->outputTemplate('index.html.twig');
	}

	/**
	 * Action for subscribe user
	 * @return null
	 */
	protected function actionSubscribe()
	{
		$error = null;
		$this->response->assign('action', self::ACTION_SUBSCRIBE);

		if ($this->request->isPost()) {

			$this->response->assign('postedData', true);
			$postData = $this->request->getPost();

			try {
				$email = $postData->getValid('email', \Supra\Validator\Type\AbstractType::EMAIL);
			} catch (Exception\ValidationFailure $e) {
				$error[] = 'wrong_email_address';
			}

			$this->response->assign('email', $postData->get('email', ''));

			$subscriberName = $postData->get('name', '');
			$subscriberName = trim($subscriberName);
			$this->response->assign('name', $subscriberName);

			if (empty($subscriberName)) {
				$error[] = 'empty_subscriber_name';
			}

			/* @var $localization PageLocalization */
			$localization = $this->getRequest()->getPageLocalization();

			if ( ! ($localization instanceof Entity\PageLocalization)) {
				return null;
			}


			//Create new subscriber
			try {
				$subscriber = $this->subscriberManager->createSubscriber($email, $subscriberName, false);
			} catch (Mailer\Exception\RuntimeException $e) {
				$error[] = 'subscriber_alredy_active';
			}

			if ( ! empty($error)) {
				$this->response->assign('error', $error);
				return;
			}


			$hash = $subscriber->getConfirmHash();

			//Create url for confirm subscribe
			$url = $this->prepareMessageUrl($email, $hash, self::ACTION_CONFIRM_SUBSCRIBE, $localization);

			/**
			 * @todo get subject and mail from-addres from configuration
			 */
			$emailParams = array(
				'subject' => $this->getPropertyValue('confirmSubscribeSubject'),
				'name' => $subscriberName,
				'link' => $url,
				'email' => $email);

			try {
				$this->sendEmail($emailParams, 'confirm_subscribe');
			} catch (\Exception $e) {
				$this->log->error("Can't send email on subscribe action; ", (string) $e);
				$error[] = 'cant_send_mail';
			}

			if (empty($error)) {
				$this->massMail->flush();
			} else {
				$this->response->assign('error', $error);
			}
		}
	}

	/**
	 * Unsubscribe action
	 * @return void
	 */
	protected function actionUnsubscribe()
	{
		$error = null;
		$this->response->assign('action', self::ACTION_UNSUBSCRIBE);

		if ($this->request->isPost()) {

			$this->response->assign('postedData', true);
			$postData = $this->request->getPost();

			try {
				$email = $postData->getValid('email', \Supra\Validator\Type\AbstractType::EMAIL);
				/**
				 * @todo add required exception type
				 */
			} catch (Exception\ValidationFailure $e) {
				$error[] = 'wrong_email_address';
				$this->response->assign('email', $postData->get('email', ''));
				$this->response->assign('error', $error);
				return;
			}

			$this->response->assign('email', $postData->get('email', ''));


			/* @var $localization PageLocalization */
			$localization = $this->getRequest()->getPageLocalization();

			if ( ! ($localization instanceof Entity\PageLocalization)) {
				return;
			}

			//Get subscriber
			$subscriber = $this->subscriberManager->
					getSingleSubscriberByEmail($email, null, true);

			if (empty($subscriber)) {
				$error[] = 'subscriber_not_found';
				$this->response->assign('error', $error);
				return;
			}

			$subscriber->generateConfirmHash();
			$hash = $subscriber->getConfirmHash();
			$subscriberName = $subscriber->getName();

			//Create url for confirm unsubscribe
			$url = $this->prepareMessageUrl($email, $hash, self::ACTION_CONFIRM_UNSUBSCRIBE, $localization);

			/**
			 * @todo get subject and mail from-addres from configuration
			 */
			$emailParams = array(
				'subject' => $this->getPropertyValue('confirmUnsubscribeSubject'),
				'name' => $subscriberName,
				'link' => $url,
				'email' => $email);

			try {
				$this->sendEmail($emailParams, 'confirm_unsubscribe');
			} catch (\Exception $e) {
				$this->log->error("Can't send email on unsubscribe action; ", (string) $e);
				$error[] = 'cant_send_mail';
			}

			if (empty($error)) {
				$this->massMail->flush();
			}

			$this->response->assign('error', $error);
		}
	}

	/**
	 * Confirm subscribe action
	 */
	protected function actionConfirmSubscribe()
	{
		$error = false;

		$email = $this->request->getParameter('email', '');
		$hash = $this->request->getParameter('hash', '');

		$subscriberToActivate = $this->subscriberManager->getSingleSubscriberByEmail($email, $hash);

		if ( ! ($subscriberToActivate instanceof Subscriber)) {
			$error = true;
		} else {
			$this->subscriberManager->activateSubscriber($subscriberToActivate);
			$this->response->assign('name', $subscriberToActivate->getName());
		}


		$this->massMail->flush();

		$this->response->assign('email', $email);
		$this->response->assign('error', $error);
		$this->response->assign('action', self::ACTION_CONFIRM_SUBSCRIBE);
	}

	/**
	 * Action to confirm unsubscribe
	 */
	protected function actionConfirmUnsubscribe()
	{

		$error = false;

		$email = $this->request->getParameter('email', '');
		$hash = $this->request->getParameter('hash', '');

		$confirmedSubscriber = $this->subscriberManager->unsubscribeByEmail($email, $hash);
		
		if ( ! ($confirmedSubscriber instanceof Subscriber)) {
			$error = true;
		} else {
			$this->response->assign('name', $confirmedSubscriber->getName());
			$this->massMail->flush();
		}

		$this->response->assign('error', $error);
		$this->response->assign('email', $email);
		$this->response->assign('action', self::ACTION_CONFIRM_UNSUBSCRIBE);
	}


	/**
	 * Send email
	 * @param array $emailParams
	 * @param string $templateName 
	 */
	private function sendEmail($emailParams, $templateName)
	{

		$mailer = ObjectRepository::getMailer($this);
		$message = new TwigMessage();
		$message->setContext(__CLASS__);

		$message->setSubject($emailParams['subject'])
				->setFrom($this->getPropertyValue('emailFromAddress'), $this->getPropertyValue('emailFromName'))
				->setTo($emailParams['email'], $emailParams['name'])
				->setBody("mail-template/{$templateName}.twig", $emailParams, 'text/html');

		$mailer->send($message);
	}

	/**
	 *
	 * @param string $email
	 * @param string $hash
	 * @param string $action
	 * @param PageLocalization $localization
	 * @return string
	 */
	private function prepareMessageUrl($email, $hash, $action, $localization)
	{
		$url = ObjectRepository::getSystemInfo($this)
				->getHostName(\Supra\Info::WITH_SCHEME);
		$url.= $localization->getPath()->
				getFullPath(Path::FORMAT_BOTH_DELIMITERS);
		$url.= "?hash={$hash}&email={$email}&action=";
		$url.= $action;

		return $url;
	}

	/**
	 * Loads property definition array
	 * @return array
	 */
	public function getPropertyDefinition()
	{
		$contents = array();

		$hostName = ObjectRepository::getSystemInfo($this)
				->getHostName(\Supra\Info::NO_SCHEME);

		$stringValue = new \Supra\Editable\LabelString("Email from address");
		$stringValue->setDefaultValue('no-reply@' . $hostName);
		$contents['emailFromAddress'] = $stringValue;

		$stringValue = new \Supra\Editable\LabelString("Email from name");
		$stringValue->setDefaultValue($hostName);
		$contents['emailFromName'] = $stringValue;

		$stringValue = new \Supra\Editable\LabelString("Email subscribe confirm subject");
		$stringValue->setDefaultValue('Confirm subscription');
		$contents['confirmSubscribeSubject'] = $stringValue;

		$stringValue = new \Supra\Editable\LabelString("Email unsubscribe confirm subject");
		$stringValue->setDefaultValue('Confirm unsubscribe');
		$contents['confirmUnsubscribeSubject'] = $stringValue;

		//Error messages
		$stringValue = new \Supra\Editable\LabelString("Wrong email format");
//		$stringValue->setGroupLabel('Error case message');
		$stringValue->setDefaultValue('Wrong email address');
		$contents['wrong_email_address'] = $stringValue;
		
		$stringValue = new \Supra\Editable\LabelString("Empty subscriber name");
//		$stringValue->setGroupLabel('Error case message');		
		$stringValue->setDefaultValue('Empty subscriber name');
		$contents['empty_subscriber_name'] = $stringValue;
		
		$stringValue = new \Supra\Editable\LabelString("Subscriber already activated");
//		$stringValue->setGroupLabel('Error case message');
		$stringValue->setDefaultValue('Subscriber already activated');
		$contents['subscriber_alredy_active'] = $stringValue;
		
		$stringValue = new \Supra\Editable\LabelString("Error on sending email message");
//		$stringValue->setGroupLabel('Error case message');		
		$stringValue->setDefaultValue("Can't send email");
		$contents['cant_send_mail'] = $stringValue;

		$stringValue = new \Supra\Editable\LabelString("Wrong confirmation URL");
//		$stringValue->setGroupLabel('Error case message');		
		$stringValue->setDefaultValue("Your confirm link is wrong or expired");
		$contents['wrong_confirm_url'] = $stringValue;

		$stringValue = new \Supra\Editable\LabelString("Subscriber not found");
//		$stringValue->setGroupLabel('Error case message');		
		$stringValue->setDefaultValue("Subscriber with current email not found.");
		$contents['subscriber_not_found'] = $stringValue;
		
		//Labels
		$stringValue = new \Supra\Editable\LabelString("Name");
//		$stringValue->setGroupLabel('Labels');		
		$stringValue->setDefaultValue("Name:");
		$contents['Name'] = $stringValue;

		$stringValue = new \Supra\Editable\LabelString("Email address");
//		$stringValue->setGroupLabel('Error case message');		
		$stringValue->setDefaultValue("Email address:");
		$contents['EmailAddress'] = $stringValue;

		$stringValue = new \Supra\Editable\LabelString("Submit button");
//		$stringValue->setGroupLabel('Error case message');		
		$stringValue->setDefaultValue("Submit");
		$contents['Submit'] = $stringValue;		
		
		//Titles
		$stringValue = new \Supra\Editable\String("Subscribe Action");
//		$stringValue->setGroupLabel('Titles');		
		$stringValue->setDefaultValue("Subscribe Action");
		$contents['SubscribeAction'] = $stringValue;

		$stringValue = new \Supra\Editable\LabelString("Subscribtion confirmation email send on ");
//		$stringValue->setGroupLabel('Titles');		
		$stringValue->setDefaultValue("Subscribtion confirmation email send on ");
		$contents['SubscribtionConfirmationEmailSendOn'] = $stringValue;

		$stringValue = new \Supra\Editable\String("Unsubscribe Action");
//		$stringValue->setGroupLabel('Titles');		
		$stringValue->setDefaultValue("Unsubscribe Action");
		$contents['UnsubscribeAction'] = $stringValue;

		$stringValue = new \Supra\Editable\LabelString("Unsubscribe confirmation email send on");
//		$stringValue->setGroupLabel('Titles');		
		$stringValue->setDefaultValue("Unsubscribe confirmation email send on");
		$contents['UnsubscribeConfirmationEmailSendOn'] = $stringValue;

		
		$stringValue = new \Supra\Editable\LabelString("Subscribtion confirmed");
//		$stringValue->setGroupLabel('Titles');		
		$stringValue->setDefaultValue("Your subscription confirmed.");
		$contents['subscribtion_confirmed'] = $stringValue;

		$stringValue = new \Supra\Editable\LabelString("Unsubscribe confirmed");
//		$stringValue->setGroupLabel('Titles');		
		$stringValue->setDefaultValue("You succesfully unsubscribed.");
		$contents['unsubscribe_success'] = $stringValue;
		
		return $contents;
	}

}