<?php

$loader = new Twig_Loader_Filesystem(SUPRA_PATH);
$twig = new Supra\Template\Parser\Twig\Twig($loader, array(
			'cache' => SUPRA_TMP_PATH,
			'auto_reload' => true,
//	'strict_variables' => true,
		));

$twig->addExtension(new Twig_Extension_Intl);

$renderEngine = new \Symfony\Bridge\Twig\Form\TwigRendererEngine(array('lib/Supra/Form/view/form_supra.html.twig'));
$renderEngine->setEnvironment($twig);
$renderer = new Symfony\Bridge\Twig\Form\TwigRenderer($renderEngine);
$renderer->setEnvironment($twig);
$twig->addExtension(new \Symfony\Bridge\Twig\Extension\FormExtension($renderer));
$twig->addExtension(
		new Symfony\Bridge\Twig\Extension\TranslationExtension(
				new Symfony\Component\Translation\IdentityTranslator(
						new \Symfony\Component\Translation\MessageSelector())));

Supra\ObjectRepository\ObjectRepository::setDefaultTemplateParser($twig);
