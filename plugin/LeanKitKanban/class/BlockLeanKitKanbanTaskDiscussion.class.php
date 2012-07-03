<?php
// Copyright 2010 SQLFusion LLC  info@sqlfusion.com
// All rights reserved
/**COPYRIGHTS**/
/**
  * LeanKit Kanban Block in Task Discussion
  * The class must extends the BaseBlock
  * setTitle() will set the Block Title
  * setContent() will set the content
  * displayBlock() call will display the block
  * isActive() is set to true by default so to inactivate the block uncomment the method isActive();
  * @package LeanKitKanban
  * @author SQLFusion
  * @license ##License##
  * @version 0.1
  * @date 2012-06-29
  */


class BlockLeanKitKanbanTaskDiscussion extends BaseBlock{
  public $short_description = 'LeanKit Kanban API Integration with Ofuz';
  public $long_description = 'LeanKit Kanban API Integration with Ofuz';

    /**
    * processBlock() , This method must be added  
    * Required to set the Block Title and The Block Content Followed by displayBlock()
    * Must extend BaseBlock
    */
  function processBlock(){
    $this->setTitle("LeanKit Kanban");
    $content = $this->getBlockConent();
    $this->setContent($content);
    $this->displayBlock();
  }

  function getBlockConent() {
    $content = "";    
    $do_pt = new ProjectTask();
    $idtask = $do_pt->getTaskId($_GET['idprojecttask']);

    $do_olk = new OfuzLeanKitKanban();
    $do_olk->getUserLoginCredentials();
    if($do_olk->getNumRows()) {
      $leankitkanban = new LeanKitKanban($do_olk->username,$do_olk->password);

      //Gets all the Boards from Kanban the API user has access to.
      $boards = $leankitkanban->getBoards('Boards');
      if(is_object($boards)) {
	// 200 => Board(s) successfully retrieved
	if($boards->ReplyCode == '200') {
	  $count_boards = count($boards->ReplyData[0]);
	  if($count_boards) {
	    $board_id = "";
	    $data = array();
	    $arr_boards = array();
	    $card_presents = false;

	    foreach($boards->ReplyData[0] as $obj_board) {
	      $data["board_id"] = $obj_board->Id;
	      $data["board_title"] = $obj_board->Title;
	      $arr_boards[] = $data;

	      $card = $leankitkanban->getCardByExternalId($obj_board->Id, $idtask);
	      //Card found in the Board
	      if($card->ReplyCode == '200') {
		$card_presents = true;
		$board_id = $obj_board->Id;
		$board_title = $obj_board->Title;
		$card_exists = $card->ReplyData[0];
	      }
	    }
	    //If card presents in a Board
	    if($card_presents) {
	      $do_olk = new OfuzLeanKitKanban();
	      $lane_name = $do_olk->getCardLaneName($board_id, $card_exists->LaneId);

	      //Card presents in a Board
	      $content .= "The Task presents in: <br /> <b>Board</b>: ".$board_title."<br /><b>Lane</b>: ".$lane_name."<br />";
	      if($card_exists->IsBlocked) {
		$content .= "Blocked: Yes";
	      }
	    } else {
	      //Card does not present in a Board
	      if(count($arr_boards)) {
		$e_board = new Event("OfuzLeanKitKanban->eventAddTaskToBoard");
		$e_board->addParam("ofuz_task_id", $idtask);
		$content .= $e_board->getFormHeader();
		$content .= $e_board->getFormEvent();
		$content .= "<div>This Task is not added to Kanban Board.</div>";
		$content .= "<div class='spacerblock_5'></div>";
		$content .= "<div><select name='board' id='board'>";
		$content .= "<option value=''>Select Board</option>";
		foreach($arr_boards as $brd) {
		  $content .= "<option value='".$brd["board_id"]."'>".$brd["board_title"]."</option>";
		}
		$content .= "</select></div>";
		$content .= "<div class='spacerblock_5'></div>";
		$content .= $e_board->getFormFooter("Add to Kanban");
	      }
	    }
	  } else {
	    //There is no Board available in Kanban
	    $content .= "There is no Board available in Kanban.";
	  }
	} else {
	  // User does not have access to any Kanban Board.
	  $content .= $boards->ReplyText;
	}
      } else {
	  // User does not have access to any Kanban Board.
	  $content .= "You do not have access to LeanKit Kanban Board.";
      }
      
    } else {
      $content .= "You have not set up your LeanKit Kanban Login Credentials.<br />Please <a href='/Setting/LeanKitKanban/leankit_kanban_authentication'>click here</a> to set up your Kanban Credentials.";
    }

    return $content;
  }
}

?>