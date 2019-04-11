<?php

abstract class Toolset_Relationship_Role_Abstract implements IToolset_Relationship_Role {

	public function __toString() {
		return $this->get_name();
	}

}