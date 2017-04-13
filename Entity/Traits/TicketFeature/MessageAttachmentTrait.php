<?php

namespace Hackzilla\Bundle\TicketBundle\Entity\Traits\TicketFeature;

use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

trait MessageAttachmentTrait
{
    /**
     * NOTE: This field is not persisted to database!
     *
     * @var File
     *@Assert\File(maxSize="2M",mimeTypes={"image/*","application/pdf"}, mimeTypesMessage = "نوع الملف غير متاح")
     * @Vich\UploadableField(mapping="ticket_message_attachment", fileNameProperty="attachmentName")
     */
    protected $attachmentFile;

    /**
     * @var string
     */
    protected $attachmentName;

    /**
     * @var int
     */
    protected $attachmentSize;

    /**
     * @var string
     */
    protected $attachmentMimeType;

    /**
     * {@inheritdoc}
     */
    public function setAttachmentFile(File $file = null)
    {
        $this->attachmentFile = $file;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttachmentFile()
    {
        return $this->attachmentFile;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachmentName($name)
    {
        $this->attachmentName = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttachmentName()
    {
        return $this->attachmentName;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachmentSize($size)
    {
        $this->attachmentSize = $size;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttachmentSize()
    {
        return $this->attachmentSize;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachmentMimeType($mimeType)
    {
        $this->attachmentMimeType =  $mimeType;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttachmentMimeType()
    {
        return $this->attachmentMimeType;
    }
}
