<?php
namespace App\Consumer\Entities;


class OrderValidationError extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $alertCode;
    /**
     * @var string
     */
    private $alertMessage;
    /**
     * @var string
     */
    private $allowUserToContinue;
    /**
     * @var string
     */
    private $alertTitle;
    /**
     * @var null|string
     */
    private $cancelText;
    /**
     * @var null|string
     */
    private $proceedText;

    /**
     * OrderValidationError constructor.
     * @param string $alertCode
     * @param string $alertMessage
     * @param string $allowUserToContinue
     * @param string $alertTitle
     * @param null|string $cancelText
     * @param null|string $proceedText
     */

    public function __construct(
        string $alertCode,
        string $alertMessage,
        bool $allowUserToContinue,
        string $alertTitle,
        ?string $cancelText,
        ?string $proceedText
    ) {
        $this->alertCode = $alertCode;
        $this->alertMessage = $alertMessage;
        $this->allowUserToContinue = $allowUserToContinue;
        $this->alertTitle = $alertTitle;
        $this->cancelText = $cancelText;
        $this->proceedText = $proceedText;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function returnAsArray()
    {
        return [
            'alert_code' => $this->getAlertCode(),
            'alert_message' => $this->getAlertMessage(),
            'allow_user_to_continue' => $this->getAllowUserToContinue(),
            'alert_title' => $this->getAlertTitle(),
            'alert_cancel_text' => $this->getCancelText(),
            'alert_proceed_text' => $this->getProceedText(),
        ];
    }

    /**
     * @return mixed
     */
    public function getAlertCode()
    {
        return $this->alertCode;
    }

    /**
     * @return mixed
     */
    public function getAlertMessage()
    {
        return $this->alertMessage;
    }

    /**
     * @return mixed
     */
    public function getAllowUserToContinue()
    {
        return $this->allowUserToContinue;
    }

    /**
     * @return mixed
     */
    public function getAlertTitle()
    {
        return $this->alertTitle;
    }

    /**
     * @return mixed
     */
    public function getCancelText()
    {
        return $this->cancelText;
    }

    /**
     * @return mixed
     */
    public function getProceedText()
    {
        return $this->proceedText;
    }


}
