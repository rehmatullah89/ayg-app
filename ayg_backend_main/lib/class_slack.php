<?php

use Httpful\Request;

class SlackMessage {

	private $webhookURL;
	private $webhookName;
	private $attachmentObjects = [];
	private $text = "";

	function __construct($webhookURL, $webhookName) {

		$this->webhookURL  = $webhookURL;
		$this->webhookName = $webhookName;
	}

	function addAttachment() {

		$obj = new SlackAttachment();

		$this->attachmentObjects[] = $obj;

		return $obj;
	}

	function setText($text) {

		$this->text = trim($text);
	}

	function send() {

		try {

			$responseArray["text"] = $this->text;

			foreach($this->attachmentObjects as $attachment) {

				$responseArray["attachments"][] = $attachment->getAttachment();
			}

			$response = Request::post($this->webhookURL)
						->body(json_encode($responseArray))
						->send();

			if($response->code != 200) {

				throw new exception ("Message send failed with code " . $response->code);
			}
		}
		catch (Exception $ex) {

			throw new exception (json_encode(json_error_return_array("AS_1055", "", "Slack message failed for " . $this->webhookName . " Message: " . json_encode($responseArray) . " Error: " . json_encode($ex->getMessage()), 1)));
		}
	}
}

class SlackAttachment {

	private $content = [];

	function __construct($content = "") {

		if(!is_array($content)) {

			$this->content["actions"] = [];
		}
		else {

			$this->content = $content;
			array_walk_recursive($this->content, "html_entity_decode_walk");
		}
	}

	function addTimestamp() {

		$this->content["ts"] = time();
	}

	function setAttribute($attributeName, $attributeValue) {

		$this->content[$attributeName] = htmlentities($attributeValue, ENT_COMPAT);
	}

	function getAttribute($attributeName) {

		if(!isset($this->content[$attributeName])) {

			return "";
		}

		return $this->content[$attributeName];
	}

	function setColorNew() {

		$this->setColor("#8a2be2");
	}

	function setColorAccepted() {

		$this->setColor("#91c917");
	}

	function setColorRejected() {

		$this->setColor("#fa1406");
	}

	function setColor($color) {

		$this->setAttribute("color", $color);
	}

	function addMarkdownAttribute($attributeName) {

		$this->content["mrkdwn_in"][] = $attributeName;
	}

	function addButtonDefault($buttonName, $buttonText, $buttonValue, $buttonIndex="") {

		return $this->addButton($buttonName, $buttonText, $buttonValue, 'default', $buttonIndex);
	}

	function addButtonPrimary($buttonName, $buttonText, $buttonValue, $buttonIndex="") {

		return $this->addButton($buttonName, $buttonText, $buttonValue, 'primary', $buttonIndex);
	}

	function addButtonDanger($buttonName, $buttonText, $buttonValue, $buttonIndex="") {

		return $this->addButton($buttonName, $buttonText, $buttonValue, 'danger', $buttonIndex);
	}

	function addButton($buttonName, $buttonText, $buttonValue, $buttonStyle, $buttonIndex) {

		if(empty($buttonIndex)) {

			$buttonIndex = count_like_php5($this->content["actions"]);
		}

		$this->content["actions"][$buttonIndex] = ["name" => $buttonName, "text" => $buttonText, "value" => $buttonValue, "type" => "button", "style" => $buttonStyle];

		return $buttonIndex;
	}

	function addConfirmToButton($buttonIndex, $confirmTitle, $confirmText, $okText="ok", $dismissText="cancel") {

		$this->content["actions"][$buttonIndex]["confirm"] = ["title" => $confirmTitle, "text" => $confirmText, "ok_text" => $okText, "dismiss_text" => $dismissText];
	}

	function addField($title, $value, $isShort=false) {

		$this->content["fields"][] = ["title" => $title, "value" => htmlentities($value, ENT_COMPAT), "short" => $isShort];
	}

	function addFieldSeparator($separator="---") {

		$this->content["fields"][] = ["title" => $separator, "value" => "", "short" => false];
	}

	function removeAllButtons() {

		$this->content["actions"] = [];
	}

	function getButtonIndexByValue($buttonValue) {

		$found = false;

		// Find the index of the action with buttonValue, return index
		foreach($this->content["actions"] as $i => $valueArray) {

			if($valueArray["value"] == $buttonValue) {

				$found = true;
				break;
			}
		}

		if($found == true) {

			return $i;
		}
		else {

			return count_like_php5($this->content["actions"]);
		}
	}

	function getAttachment() {

		return $this->content;
	}
}
