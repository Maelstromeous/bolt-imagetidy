<?php

namespace Bolt\Extension\Maelstromeous\ImageTidy;

use Bolt\Extension\SimpleExtension;
use Bolt\Events\AccessControlEvents;
use Bolt\Events\AccessControlEvent;
use Bolt\Events\StorageEvents;
use Bolt\Events\StorageEvent;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Bolt\Storage\Field\Collection\RepeatingFieldCollection;

/**
 * Extension extension class.
 *
 * @author Matt Cavanagh <maelstrome26@gmail.com>
 */
class ImageTidyExtensions extends SimpleExtension
{
    protected function registerServices(Application $app)
    {
        $app['upload'] = $app->extend(
            'upload',
            function ($handler, $app) {
                return $this->handleUpload($app, $handler);
            }
        );
    }

    /**
     * Listen for the events we need to do things for.
     *
     * @return [type] [description]
     */
    public static function getSubscribedEvents()
    {
        $parentEvents = parent::getSubscribedEvents();
        $localEvents = [
            StorageEvents::POST_SAVE => [
                ['handleCreation', 0]
            ]
        ];

        return $parentEvents + $localEvents;
    }

    /**
     * Listens for creation, then moves the files accordingly to their correct path.
     *
     * @param  Bolt\Events\StorageEvent $event
     *
     * @return [type]
     */
    public function handleCreation(StorageEvent $event)
    {
        $app = $this->container;
        if ($event->isCreate()) {
            echo 'Created!';

            $content = $event->getContent();

            // Get the entry out of the DB based off the contenttype and the ID
            $repo = $app['storage']->getRepository($content['contenttype']);
            $entity = $repo->find($content['content_id']);

            // Go through each field and pick any images
            $images = [];

            foreach ($entity->_fields as $key => $field) {
                // If flat / standard image upload
                if (is_array($field)) {
                    if (! empty($field['file'])) {
                        $images[] = $field['file'];
                    }
                }

                if ($field instanceof RepeatingFieldCollection) {
                    echo 'Repeating found!';
                    foreach ($field as $item) {
                        foreach ($item as $col) {
                            var_dump($col);
                        }
                    }
                }
            }

            #var_dump($images);

            var_dump('fields');
            var_dump($entity->_fields);

            /*foreach ($entry->contenttype->get('contentType')['fields'] as $field) {
                var_dump($field);
            }*/
            //var_dump($entity);
        }
        /*var_dump($event->getContent());
        var_dump($event);*/
        die;
    }

    /**
     * Handles the upload of an image and moves the files based on if we're
     * creating or editing things.
     *
     * @param  Silex\Application                        $app
     * @param  Symfony\Component\HttpFoundation\Request $handler
     *
     * @return Symfony\Component\HttpFoundation\Request
     */
    protected function handleUpload(Application $app, $handler) {
        // We should always have a contenttype
        $path = "/{$app['request']->get('contenttype')}";

        // Check if we're in creation mode. If so, use the slug for temp storage
        if ($app['request']->get('id') === '') {
            // If we're missing the slug, throw an exception to require it
            // NEED TO FIGURE OUT HOW TO PASS THE ERROR BACK
            /*if (empty($app['request']->get('slug'))) {
                return false;
            }*/
            $handler->setPrefix("{$path}/{$app['request']->get('slug')}/");
        } else {
            $handler->setPrefix("{$path}/{$app['request']->get('id')}/");
        }

        return $handler;
    }
}
