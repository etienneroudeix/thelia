<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Thelia\Controller\Admin;

use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Event\Message\MessageDeleteEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\Message\MessageUpdateEvent;
use Thelia\Core\Event\Message\MessageCreateEvent;
use Thelia\Model\MessageQuery;
use Thelia\Form\MessageModificationForm;
use Thelia\Form\MessageCreationForm;
use Symfony\Component\Finder\Finder;
use Thelia\Core\Template\TemplateHelper;

/**
 * Manages messages sent by mail
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class MessageController extends AbstractCrudController
{
    public function __construct()
    {
        parent::__construct(
            'message',
            null, // no sort order change
            null, // no sort order change
            AdminResources::MESSAGE,
            TheliaEvents::MESSAGE_CREATE,
            TheliaEvents::MESSAGE_UPDATE,
            TheliaEvents::MESSAGE_DELETE,
            null, // No visibility toggle
            null  // No position update
        );
    }

    protected function getCreationForm()
    {
        return new MessageCreationForm($this->getRequest());
    }

    protected function getUpdateForm()
    {
        return new MessageModificationForm($this->getRequest());
    }

    protected function getCreationEvent($formData)
    {
        $createEvent = new MessageCreateEvent();

        $createEvent
            ->setMessageName($formData['name'])
            ->setLocale($formData["locale"])
            ->setTitle($formData['title'])
            ->setSecured($formData['secured'] ? true : false)
            ;

        return $createEvent;
    }

    protected function getUpdateEvent($formData)
    {
        $changeEvent = new MessageUpdateEvent($formData['id']);

        // Create and dispatch the change event
        $changeEvent
            ->setMessageName($formData['name'])
            ->setSecured($formData['secured'])
            ->setLocale($formData["locale"])
            ->setTitle($formData['title'])
            ->setSubject($formData['subject'])
            ->setHtmlLayoutFileName($formData['html_layout_file_name'])
            ->setHtmlTemplateFileName($formData['html_template_file_name'])
            ->setTextLayoutFileName($formData['text_layout_file_name'])
            ->setTextTemplateFileName($formData['text_template_file_name'])
            ->setHtmlMessage($formData['html_message'])
            ->setTextMessage($formData['text_message'])
        ;

        return $changeEvent;
    }

    protected function getDeleteEvent()
    {
        return new MessageDeleteEvent($this->getRequest()->get('message_id'));
    }

    protected function eventContainsObject($event)
    {
        return $event->hasMessage();
    }

    protected function hydrateObjectForm($object)
    {
        // Prepare the data that will hydrate the form
        $data = array(
            'id'            => $object->getId(),
            'name'          => $object->getName(),
            'secured'       => $object->getSecured() ? true : false,
            'locale'        => $object->getLocale(),
            'title'         => $object->getTitle(),
            'subject'       => $object->getSubject(),
            'html_message'  => $object->getHtmlMessage(),
            'text_message'  => $object->getTextMessage(),

            'html_layout_file_name'   => $object->getHtmlLayoutFileName(),
            'html_template_file_name' => $object->getHtmlTemplateFileName(),
            'text_layout_file_name'   => $object->getTextLayoutFileName(),
            'text_template_file_name' => $object->getTextTemplateFileName(),
        );

        // Setup the object form
        return new MessageModificationForm($this->getRequest(), "form", $data);
    }

    protected function getObjectFromEvent($event)
    {
        return $event->hasMessage() ? $event->getMessage() : null;
    }

    protected function getExistingObject()
    {
        $message = MessageQuery::create()
        ->findOneById($this->getRequest()->get('message_id', 0));

        if (null !== $message) {
            $message->setLocale($this->getCurrentEditionLocale());
        }

        return $message;
    }

    protected function getObjectLabel($object)
    {
        return $object->getName();
    }

    protected function getObjectId($object)
    {
        return $object->getId();
    }

    protected function renderListTemplate($currentOrder)
    {
        return $this->render('messages');
    }

    protected function listDirectoryContent($requiredExtension)
    {
        $list = array();

        $dir = TemplateHelper::getInstance()->getActiveMailTemplate()->getAbsolutePath();

        $finder = Finder::create()->files()->in($dir)->ignoreDotFiles(true)->sortByName()->name("*.$requiredExtension");

        foreach ($finder as $file) {
            $list[] = $file->getBasename();
        }

        return $list;
    }

    protected function renderEditionTemplate()
    {
        return $this->render('message-edit', array(
                'message_id'         => $this->getRequest()->get('message_id'),
                'layout_list'        => $this->listDirectoryContent('tpl'),
                'html_template_list' =>  $this->listDirectoryContent('html'),
                'text_template_list' =>  $this->listDirectoryContent('txt'),
        ));
    }

    protected function redirectToEditionTemplate()
    {
        return $this->generateRedirectFromRoute(
            "admin.configuration.messages.update",
            [
                'message_id' => $this->getRequest()->get('message_id')
            ]
        );
    }

    protected function redirectToListTemplate()
    {
        return $this->generateRedirectFromRoute('admin.configuration.messages.default');
    }
}
