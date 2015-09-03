<?php
require_once('./Services/Object/classes/class.ilObject2.php');
require_once('./Services/UIComponent/Button/classes/class.ilLinkButton.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/classes/voting/class.xlvoVotingManager.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/classes/voting/display/class.xlvoDisplayPlayerGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/classes/class.ilObjLiveVotingAccess.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/classes/class.ilObjLiveVotingAccess.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/classes/class.ilLiveVotingPlugin.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/classes/class.xlvoVotingType.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/classes/voting/class.xlvoVoterGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/classes/voting/class.xlvoOption.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/classes/voting/class.xlvoPlayer.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/classes/voting/class.xlvoVotingManager.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/classes/voting/class.xlvoMultiLineInputGUI.php');

/**
 * Class xlvoPlayerGUI
 */
class xlvoPlayerGUI {

	const TAB_STANDARD = 'tab_voter';
	const IDENTIFIER = 'xlvoVot';
	const CMD_STANDARD = 'startVoting';
	const CMD_SHOW_VOTING = 'showVoting';
	const CMD_START_VOTING = 'startVoting';
	const CMD_NEXT = 'nextVoting';
	const CMD_PREVIOUS = 'previousVoting';
	const CMD_FREEZE = 'freeze';
	const CMD_UNFREEZE = 'unfreeze';
	const CMD_RESET = 'resetVotes';
	const CMD_TERMINATE = 'terminate';
	const CMD_END_OF_VOTING = 'endOfVoting';
	const CMD_START_OF_VOTING = 'startOfVoting';
	/**
	 * @var ilTemplate
	 */
	public $tpl;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;
	/**
	 * @var ilObjLiveVotingAccess
	 */
	protected $access;
	/**
	 * @var ilLiveVotingPlugin
	 */
	protected $pl;
	/**
	 * @var ilObjUser
	 */
	protected $usr;
	/**
	 * @var int
	 */
	protected $obj_id;
	/**
	 * @var xlvoVotingManager
	 */
	protected $voting_manager;


	/**
	 *
	 */
	public function __construct() {
		global $tpl, $ilCtrl, $ilTabs, $ilUser, $ilToolbar;
		$tpl->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/templates/default/voting/display/display_player.js');

		/**
		 * @var $tpl       ilTemplate
		 * @var $ilCtrl    ilCtrl
		 * @var $ilTabs    ilTabsGUI
		 * @var $ilUser    ilObjUser
		 * @var $ilToolbar ilToolbarGUI
		 */
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->tabs = $ilTabs;
		$this->usr = $ilUser;
		$this->toolbar = $ilToolbar;
		$this->access = new ilObjLiveVotingAccess();
		$this->pl = ilLiveVotingPlugin::getInstance();
		$this->voting_manager = new xlvoVotingManager();
		$this->obj_id = ilObject2::_lookupObjId($_GET['ref_id']);
	}


	/**
	 *
	 */
	public function executeCommand() {
		$this->tabs->addTab(self::TAB_STANDARD, $this->pl->txt('player'), $this->ctrl->getLinkTarget($this, self::CMD_STANDARD));
		$this->tabs->setTabActive(self::TAB_STANDARD);
		$nextClass = $this->ctrl->getNextClass();
		switch ($nextClass) {
			default:
				if ($this->access->hasWriteAccess()) {
					$cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
					$this->{$cmd}();
					break;
				} else {
					ilUtil::sendFailure(ilLiveVotingPlugin::getInstance()->txt('permission_denied'), true);
					break;
				}
		}
	}


	/**
	 *
	 */
	public function startVoting() {
		if (! $this->access->hasWriteAccess()) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
		} else {
			$vo = $this->voting_manager->getVotings($this->obj_id, true)->first();
			if ($vo == NULL) {
				ilUtil::sendInfo($this->pl->txt('msg_no_voting_available'), true);
				$this->ctrl->redirect(new xlvoVotingGUI(), xlvoVotingGUI::CMD_STANDARD);
			} else {
				$this->setActiveVoting($vo->getId());
				$this->ctrl->setParameter(new xlvoPlayerGUI(), self::IDENTIFIER, $vo->getId());
				$this->ctrl->redirect(new xlvoPlayerGUI(), self::CMD_SHOW_VOTING);
			}
		}
	}


	/**
	 * @param null $voting_id
	 */
	public function showVoting($voting_id = NULL) {

		if ($voting_id == NULL) {
			if ($_GET[self::IDENTIFIER] != NULL) {
				$voting_id = $_GET[self::IDENTIFIER];
			} else {
				$voting_id = 0;
			}
		}

		if ($voting_id != 0) {
			/**
			 * @var xlvoVoting $xlvoVoting
			 */
			$xlvoVoting = $this->voting_manager->getVoting($voting_id);

			if (! $this->access->hasWriteAccessForObject($xlvoVoting->getObjId(), $this->usr->getId())) {
				// TODO send Failure
				ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			} else {

				/**
				 * @var boolean $isAvailable
				 */
				$isAvailable = $this->voting_manager->isVotingAvailable($xlvoVoting->getObjId());
				/**
				 * @var xlvoPlayer $xlvoPlayer
				 */
				$xlvoPlayer = $this->voting_manager->getPlayer($xlvoVoting->getObjId());
				if ($xlvoPlayer instanceof xlvoPlayer) {
					$isRunning = $xlvoPlayer->getStatus();

					if ($isAvailable && $isRunning == xlvoPlayer::STAT_RUNNING) {

						$this->initToolbar();

						$this->setActiveVoting($xlvoVoting->getId());

						$display = new xlvoDisplayPlayerGUI($xlvoVoting);

						$this->tpl->setContent($display->getHTML());

						return $display->getHTML();
					} else {
						ilUtil::sendFailure($this->pl->txt('msg_voting_not_available'), false);
					}
				} else {
					ilUtil::sendFailure($this->pl->txt('msg_voting_not_available'), false);
				}
			}
		} else {
			ilUtil::sendFailure($this->pl->txt('msg_voting_not_available'), false);
		}
	}


	/**
	 * @param $voting_id
	 */
	public function setActiveVoting($voting_id) {
		/**
		 * @ xlvoVoting $xlvoVoting
		 */
		$xlvoVoting = $this->voting_manager->getVoting($voting_id);
		$xlvoPlayer = $this->voting_manager->getPlayer($xlvoVoting->getObjId());
		if ($xlvoPlayer == NULL) {
			$xlvoPlayer = new xlvoPlayer();
			$xlvoPlayer->setObjId($xlvoVoting->getObjId());
			$xlvoPlayer->setActiveVoting($voting_id);
			$xlvoPlayer->setReset(xlvoPlayer::RESET_OFF);
			$xlvoPlayer->setStatus(xlvoPlayer::STAT_START_VOTING);
			$xlvoPlayer->create();
		} else {
			$xlvoPlayer->setActiveVoting($voting_id);
			$xlvoPlayer->setStatus(xlvoPlayer::STAT_RUNNING);
			$xlvoPlayer->update();
		}
	}


	/**
	 * @param $obj_id
	 *
	 * @return int
	 */
	public function getActiveVoting($obj_id) {
		$xlvoPlayer = $this->voting_manager->getPlayer($obj_id);

		if ($xlvoPlayer instanceof xlvoPlayer) {
			return $xlvoPlayer->getActiveVoting();
		} else {
			return 0;
		}
	}

	
	public function nextVoting() {
		if (! $this->access->hasWriteAccess()) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
		} else {
			$voting_id_current = $this->getActiveVoting($this->obj_id);
			$votings = $this->voting_manager->getVotings($this->obj_id, true)->getArray();
			$voting_last = $this->voting_manager->getVotings($this->obj_id, true)->last();

			$voting_id_next = $voting_id_current;
			$get_next_elem = false;
			foreach ($votings as $key => $voting) {
				if ($get_next_elem) {
					$voting_id_next = $voting['id'];
					break;
				}
				if ($voting['id'] == $voting_id_current) {
					$get_next_elem = true;
				}
			}

			if ($voting_id_current == $voting_last->getId()) {
				$this->ctrl->redirect(new xlvoPlayerGUI(), self::CMD_END_OF_VOTING);
			}
			$this->ctrl->setParameter(new xlvoPlayerGUI(), self::IDENTIFIER, $voting_id_next);
			$this->ctrl->redirect(new xlvoPlayerGUI(), self::CMD_SHOW_VOTING);
		}
	}


	public function previousVoting() {
		if (! $this->access->hasWriteAccess()) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
		} else {
			$voting_id_current = $this->getActiveVoting($this->obj_id);
			$votings = array_reverse($this->voting_manager->getVotings($this->obj_id, true)->getArray());
			$voting_first = $this->voting_manager->getVotings($this->obj_id, true)->first();

			$voting_id_previous = $voting_id_current;
			$get_next_elem = false;
			foreach ($votings as $key => $voting) {
				if ($get_next_elem) {
					$voting_id_previous = $voting['id'];
					break;
				}
				if ($voting['id'] == $voting_id_current) {
					$get_next_elem = true;
				}
			}

			if ($voting_id_current == $voting_first->getId()) {
				$this->ctrl->redirect(new xlvoPlayerGUI(), self::CMD_START_OF_VOTING);
			}

			$this->ctrl->setParameter(new xlvoPlayerGUI(), self::IDENTIFIER, $voting_id_previous);
			$this->ctrl->redirect(new xlvoPlayerGUI(), self::CMD_SHOW_VOTING);
		}
	}


	/**
	 * Display the Page before the first Voting. Start of the Voting.
	 */
	public function startOfVoting() {
		if (! $this->access->hasWriteAccess()) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
		} else {
			$xlvoPlayer = $this->voting_manager->getPlayer($this->obj_id);
			if ($xlvoPlayer instanceof xlvoPlayer) {
				$xlvoPlayer->setStatus(xlvoPlayer::STAT_START_VOTING);
				$xlvoPlayer->update();
			} else {
				$vo = $this->voting_manager->getVotings($this->obj_id, true)->first();
				if ($vo == NULL) {
					ilUtil::sendInfo($this->pl->txt('msg_no_voting_available'), true);
					$this->ctrl->redirect(new xlvoVotingGUI(), xlvoVotingGUI::CMD_STANDARD);
				} else {
					$this->setActiveVoting($vo->getId());
				}
			}

			$this->setContentStartOfVoting();
		}
	}


	/**
	 * Display the Page after last Voting. End of the Voting.
	 */
	public function endOfVoting() {
		if (! $this->access->hasWriteAccess()) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
		} else {
			/**
			 * @var xlvoPlayer $xlvoPlayer
			 */
			$xlvoPlayer = $this->voting_manager->getPlayer($this->obj_id);
			$reset_voting_id = 0;
			$xlvoPlayer->setActiveVoting($reset_voting_id);
			$xlvoPlayer->setStatus(xlvoPlayer::STAT_END_VOTING);
			$xlvoPlayer->update();

			$this->setContentEndOfVoting();
		}
	}


	/**
	 * Deletes votes for a voting.
	 *
	 * @param $voting_id
	 */
	public function resetVotes($voting_id) {
		/**
		 * @var xlvoVoting $xlvoVoting
		 */
		$xlvoVoting = $this->voting_manager->getVoting($voting_id);
		if (! $this->access->hasWriteAccessForObject($xlvoVoting->getObjId(), $this->usr->getId())) {
			// TODO send failure
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
		} else {
			/**
			 * @var xlvoVoting $xlvoVoting
			 */
			$xlvoVoting = xlvoVoting::find($voting_id);
			/**
			 * @var xlvoPlayer $xlvoPlayer
			 */
			$xlvoPlayer = $this->voting_manager->getPlayer($xlvoVoting->getObjId());
			$xlvoPlayer->setReset(xlvoPlayer::RESET_ON);
			$xlvoPlayer->update();

			$this->voting_manager->deleteVotesForVoting($voting_id);

			// wait 5 seconds. voter pages can be updated during this time.
			sleep(5);

			$xlvoPlayer->setReset(xlvoPlayer::RESET_OFF);
			$xlvoPlayer->update();
		}
	}


	/**
	 * Set Player Status to frozen.
	 *
	 * @param $obj_id
	 */
	public function freeze($obj_id) {
		if (! $this->access->hasWriteAccessForObject($obj_id, $this->usr->getId())) {
			// TODO send failure
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
		} else {
			/**
			 * @var xlvoVotingConfig $xlvoVotingConfig
			 */
			$xlvoVotingConfig = xlvoVotingConfig::find($obj_id);
			$xlvoVotingConfig->setFrozen(true);
			$xlvoVotingConfig->update();
		}
	}


	/**
	 * Set Player Status to unfrozen.
	 *
	 * @param $obj_id
	 */
	public function unfreeze($obj_id) {
		if (! $this->access->hasWriteAccessForObject($obj_id, $this->usr->getId())) {
			// TODO send failure
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
		} else {
			/**
			 * @var xlvoVotingConfig $xlvoVotingConfig
			 */
			$xlvoVotingConfig = xlvoVotingConfig::find($obj_id);
			$xlvoVotingConfig->setFrozen(false);
			$xlvoVotingConfig->update();
		}
	}


	/**
	 * Change Player Status of Voting to terminated and redirect to start of voting.
	 */
	public function terminate() {
		if (! $this->access->hasWriteAccess()) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
		} else {
			/**
			 * @var xlvoPlayer $xlvoPlayer
			 */
			$this->unfreeze($this->obj_id);
			$xlvoPlayer = $this->voting_manager->getPlayer($this->obj_id);
			$xlvoPlayer->setStatus(xlvoPlayer::STAT_STOPPED);
			$xlvoPlayer->update();
			$this->ctrl->redirect(new xlvoVotingGUI(), xlvoVotingGUI::CMD_STANDARD);
		}
	}


	/**
	 * Set Toolbar Content and Buttons for the Player.
	 */
	protected function initToolbar() {
		$current_selection_list = new ilAdvancedSelectionListGUI();
		$current_selection_list->setListTitle($this->pl->txt('voting'));
		$current_selection_list->setId('xlvo_select');
		$current_selection_list->setTriggerEvent('xlvo_voting');
		$current_selection_list->setUseImages(false);
		/**
		 * @var xlvoVoting[] $votings
		 */
		$votings = $this->voting_manager->getVotings($this->obj_id, true)->get();
		foreach ($votings as $voting) {
			$this->ctrl->setParameter(new xlvoPlayerGUI(), self::IDENTIFIER, $voting->getId());
			$current_selection_list->addItem($voting->getTitle(), $voting->getId(), $this->ctrl->getLinkTarget(new xlvoPlayerGUI(), self::CMD_SHOW_VOTING));
		}
		$this->toolbar->addText($current_selection_list->getHTML());

		$b = ilLinkButton::getInstance();
		$b->setCaption('rep_robj_xlvo_back');
		$b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_PREVIOUS));
		$b->setId('btn-previous');
		$this->toolbar->addButtonInstance($b);

		$b = ilLinkButton::getInstance();
		$b->setCaption('rep_robj_xlvo_next');
		$b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_NEXT));
		$b->setId('btn-next');
		$this->toolbar->addButtonInstance($b);

		$this->toolbar->addSeparator();

		$b = ilLinkButton::getInstance();
		$b->setCaption('rep_robj_xlvo_freeze');
		$b->setUrl('#');
		$b->setId('btn-freeze');
		$this->toolbar->addButtonInstance($b);

		$b = ilLinkButton::getInstance();
		$b->setCaption('rep_robj_xlvo_unfreeze');
		$b->setUrl('#');
		$b->setId('btn-unfreeze');
		$this->toolbar->addButtonInstance($b);

		$b = ilLinkButton::getInstance();
		$b->setCaption('rep_robj_xlvo_terminate');
		$b->setUrl($this->ctrl->getLinkTarget(new xlvoPlayerGUI(), self::CMD_TERMINATE));
		$b->setId('btn-terminate');
		$this->toolbar->addButtonInstance($b);

		$this->toolbar->addSeparator();

		$b = ilLinkButton::getInstance();
		$b->setCaption('rep_robj_xlvo_reset');
		$b->setUrl('#');
		$b->setId('btn-reset');
		$this->toolbar->addButtonInstance($b);

		$b = ilLinkButton::getInstance();
		$b->setCaption('rep_robj_xlvo_hide_results');
		$b->setUrl('#');
		$b->setId('btn-hide-results');
		$this->toolbar->addButtonInstance($b);

		$b = ilLinkButton::getInstance();
		$b->setCaption('rep_robj_xlvo_show_results');
		$b->setUrl('#');
		$b->setId('btn-show-results');
		$this->toolbar->addButtonInstance($b);
	}


	/**
	 * Set GUI Content for template at the start of Voting.
	 */
	protected function setContentStartOfVoting() {

		$b = ilLinkButton::getInstance();
		$b->setCaption('rep_robj_xlvo_start_voting');
		$b->setUrl($this->ctrl->getLinkTarget(new xlvoPlayerGUI(), self::CMD_START_VOTING));
		$b->setId('btn-start-voting');
		$this->toolbar->addButtonInstance($b);

		$b = ilLinkButton::getInstance();
		$b->setCaption('rep_robj_xlvo_terminate');
		$b->setUrl($this->ctrl->getLinkTarget(new xlvoPlayerGUI(), self::CMD_TERMINATE));
		$b->setId('btn-terminate');
		$this->toolbar->addButtonInstance($b);

		$template = new ilTemplate('./Customizing/global/plugins/Services/Repository/RepositoryObject/LiveVoting/templates/default/voting/display/tpl.player_start_screen.html', true, true);
		/**
		 * @var xlvoVotingConfig $xlvoVotingConfig
		 */
		$xlvoVotingConfig = $this->voting_manager->getVotingConfig($this->obj_id);
		$template->setVariable('PIN', $xlvoVotingConfig->getPin());
		$template->setVariable('TITLE', $this->pl->txt('msg_start_of_voting_title'));
		$template->setVariable('QR-CODE', 'QR-CODE');

		$this->tpl->setContent($template->get());
	}


	/**
	 * Set GUI Content for template at the end of Voting.
	 */
	protected function setContentEndOfVoting() {

		$bb = ilLinkButton::getInstance();
		$bb->setCaption('rep_robj_xlvo_back_to_voting');
		$bb->setUrl($this->ctrl->getLinkTarget(new xlvoPlayerGUI(), self::CMD_START_VOTING));
		$bb->setId('btn-back-to-voting');

		$bt = ilLinkButton::getInstance();
		$bt->setCaption('rep_robj_xlvo_terminate');
		$bt->setUrl($this->ctrl->getLinkTarget(new xlvoPlayerGUI(), self::CMD_TERMINATE));
		$bt->setId('btn-terminate');

		$this->toolbar->addButtonInstance($bb);
		$this->toolbar->addButtonInstance($bt);

		$this->tpl->setContent($this->pl->txt('msg_end_of_voting'));
	}
}