<?php

namespace Photo;

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Doctrine\ORM\Events;
use Exception;
use Laminas\Cache\StorageFactory;
use Laminas\Mvc\MvcEvent;
use Interop\Container\ContainerInterface;
use League\Glide\Urls\UrlBuilderFactory;
use Photo\Listener\AlbumDate as AlbumDateListener;
use Photo\Listener\Remove as RemoveListener;
use Photo\Service\Admin;
use Photo\Service\Album;
use Photo\Service\AlbumCover;
use Photo\Service\Metadata;
use Photo\Service\Photo;
use Photo\View\Helper\GlideUrl;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $container = $e->getApplication()->getServiceManager();
        $em = $container->get('doctrine.entitymanager.orm_default');
        $dem = $em->getEventManager();
        $dem->addEventListener([Events::prePersist], new AlbumDateListener());
        $photoService = $container->get('photo_service_photo');
        $albumService = $container->get('photo_service_album');
        $dem->addEventListener([Events::preRemove], new RemoveListener($photoService, $albumService));
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'photo_service_album' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('photo_acl');
                    $photoService = $container->get('photo_service_photo');
                    $albumCoverService = $container->get('photo_service_album_cover');
                    $memberService = $container->get('decision_service_member');
                    $storageService = $container->get('application_service_storage');
                    $albumMapper = $container->get('photo_mapper_album');
                    $createAlbumForm = $container->get('photo_form_album_create');
                    $editAlbumForm = $container->get('photo_form_album_edit');

                    return new Album(
                        $translator,
                        $userRole,
                        $acl,
                        $photoService,
                        $albumCoverService,
                        $memberService,
                        $storageService,
                        $albumMapper,
                        $createAlbumForm,
                        $editAlbumForm
                    );
                },
                'photo_service_metadata' => function () {
                    return new Metadata();
                },
                'photo_service_photo' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('photo_acl');
                    $memberService = $container->get('decision_service_member');
                    $storageService = $container->get('application_service_storage');
                    $photoMapper = $container->get('photo_mapper_photo');
                    $albumMapper = $container->get('photo_mapper_album');
                    $tagMapper = $container->get('photo_mapper_tag');
                    $hitMapper = $container->get('photo_mapper_hit');
                    $voteMapper = $container->get('photo_mapper_vote');
                    $weeklyPhotoMapper = $container->get('photo_mapper_weekly_photo');
                    $profilePhotoMapper = $container->get('photo_mapper_profile_photo');
                    $photoConfig = $container->get('config')['photo'];

                    return new Photo(
                        $translator,
                        $userRole,
                        $acl,
                        $memberService,
                        $storageService,
                        $photoMapper,
                        $albumMapper,
                        $tagMapper,
                        $hitMapper,
                        $voteMapper,
                        $weeklyPhotoMapper,
                        $profilePhotoMapper,
                        $photoConfig
                    );
                },
                'photo_service_album_cover' => function (ContainerInterface $container) {
                    $photoMapper = $container->get('photo_mapper_photo');
                    $albumMapper = $container->get('photo_mapper_album');
                    $storage = $container->get('application_service_storage');
                    $photoConfig = $container->get('config')['photo'];
                    $storageConfig = $container->get('config')['storage'];

                    return new AlbumCover($photoMapper, $albumMapper, $storage, $photoConfig, $storageConfig);
                },
                'photo_service_admin' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('photo_acl');
                    $photoService = $container->get('photo_service_photo');
                    $albumService = $container->get('photo_service_album');
                    $metadataService = $container->get('photo_service_metadata');
                    $storageService = $container->get('application_service_storage');
                    $photoMapper = $container->get('photo_mapper_photo');
                    $photoConfig = $container->get('config')['photo'];

                    return new Admin(
                        $translator,
                        $userRole,
                        $acl,
                        $photoService,
                        $albumService,
                        $metadataService,
                        $storageService,
                        $photoMapper,
                        $photoConfig
                    );
                },
                'photo_form_album_edit' => function (ContainerInterface $container) {
                    $form = new Form\EditAlbum(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('photo_hydrator_album'));

                    return $form;
                },
                'photo_form_album_create' => function (ContainerInterface $container) {
                    $form = new Form\CreateAlbum(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('photo_hydrator_album'));

                    return $form;
                },
                'photo_hydrator_album' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                        'Photo\Model\Album'
                    );
                },
                'photo_mapper_album' => function (ContainerInterface $container) {
                    return new Mapper\Album(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_photo' => function (ContainerInterface $container) {
                    return new Mapper\Photo(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_profile_photo' => function (ContainerInterface $container) {
                    return new Mapper\ProfilePhoto(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_tag' => function (ContainerInterface $container) {
                    return new Mapper\Tag(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_hit' => function (ContainerInterface $container) {
                    return new Mapper\Hit(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_weekly_photo' => function (ContainerInterface $container) {
                    return new Mapper\WeeklyPhoto(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_vote' => function (ContainerInterface $container) {
                    return new Mapper\Vote(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_acl' => function (ContainerInterface $container) {
                    $acl = $container->get('acl');

                    // add resources for this module
                    $acl->addResource('photo');
                    $acl->addResource('album');
                    $acl->addResource('tag');

                    // Only users and 'the screen' are allowed to view photos and albums
                    $acl->allow('user', 'photo', 'view');
                    $acl->allow('user', 'album', 'view');

                    $acl->allow('apiuser', 'photo', 'view');
                    $acl->allow('apiuser', 'album', 'view');

                    // Users are allowed to view, remove and add tags
                    $acl->allow('user', 'tag', ['view', 'add', 'remove']);

                    // Users are allowed to download photos
                    $acl->allow('user', 'photo', ['download', 'view_metadata']);

                    $acl->allow('photo_guest', 'photo', 'view');
                    $acl->allow('photo_guest', 'album', 'view');
                    $acl->allow('photo_guest', 'photo', ['download', 'view_metadata']);

                    return $acl;
                },
                'album_page_cache' => function () {
                    return StorageFactory::factory(
                        [
                            'adapter' => [
                                'name' => 'filesystem',
                                'options' => [
                                    'dirLevel' => 2,
                                    'cacheDir' => 'data/cache',
                                    'dirPermission' => 0755,
                                    'filePermission' => 0666,
                                    'namespaceSeparator' => '-db-',
                                ],
                            ],
                            'plugins' => ['serializer'],
                        ]
                    );
                },
            ],
        ];
    }

    public function getViewHelperConfig()
    {
        return [
            'factories' => [
                'glideUrl' => function (ContainerInterface $container) {
                    $helper = new GlideUrl();
                    $config = $container->get('config');
                    if (
                        !isset($config['glide']) || !isset($config['glide']['base_url'])
                        || !isset($config['glide']['signing_key'])
                    ) {
                        throw new Exception('Invalid glide configuration');
                    }

                    $urlBuilder = UrlBuilderFactory::create(
                        $config['glide']['base_url'],
                        $config['glide']['signing_key']
                    );
                    $helper->setUrlBuilder($urlBuilder);

                    return $helper;
                },
            ],
        ];
    }
}
