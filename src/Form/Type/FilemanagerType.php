<?php

namespace Keyboardman\FilemanagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends AbstractType<string|null>
 */
class FilemanagerType extends AbstractType
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'crossdomain' => false, // valeur par défaut
            'media' => null, // option facultative
            'token' => null,
        ]);

        $resolver->setAllowedTypes('crossdomain', 'bool');
        $resolver->setAllowedTypes('media', ['null', 'string']);
        $resolver->setAllowedTypes('token', ['null', 'string']);
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $params = [
            'mode' => 'iframe',
            'target' => $view->vars['id'],
        ];

        if ($options['crossdomain']) {
            $params['crossdomain'] = 1;
        }

        if (!empty($options['media'])) {
            // Si array → on convertit en string (image,video,...)
            $params['media'] = $options['media'];
        }

        if (!empty($options['token'])) {
            $params['token'] = $options['token'];
        }

        $url = $this->urlGenerator->generate(
            'keyboardman_filemanager',
            $params,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $view->vars['filemanager_url'] = $url;
        $view->vars['crossdomain'] = $options['crossdomain'];
        $view->vars['media'] = $options['media'];
        $view->vars['token'] = $options['token'];
    }

    public function getBlockPrefix(): string
    {
        return 'filemanager';
    }
}
