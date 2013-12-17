<?php
// vim: set ai ts=4 sw=4 ft=php:

/*
 * This is FreePBX Big Module Object.
 *
 * Copyright 2013 Rob Thomas <rob.thomas@schmoozecom.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class FreePBX {
	public function __construct() {
		// Preload the default libraries into the class. There should be
		// very few of these, as they will normally get instantiated when
		// they're asked for the first time.
		//
		// Currently this is only "Config". 
		$libraries = $this->listDefaultLibraries();

		$oldIncludePath = get_include_path();
		set_include_path(__DIR__.":".get_include_path());
		foreach ($libraries as $lib) {

			if (class_exists($lib)) 
				throw new Exception("Somehow, the class $lib already exists");

			include "$lib.class.php";
			$this->$lib = new $lib($this);
		}
		// set_include_path($oldIncludePath);
	}

	public function __get($var) {
		return $this->autoLoad($var);
	}

	public function __call($var, $args) {
		return $this->autoLoad($var, $args);
	}

	private function autoLoad() {
		// Figure out what is wanted, and return it.
		if (func_num_args() == 0)
			throw new Exception("Nothing given to the AutoLoader");

		// If we have TWO arguments, we've been __called, if we only have 
		// one and we've been called by __get.

		$args = func_get_args();
		$var = $args[0];

		// Ensure no-one's trying to include something with a path in it.
		if (strpos($var, "/") || strpos($var, ".."))
			throw new Exception("Invalid include given to AutoLoader - $var");

		// Does this exist as a default Library?
		if (file_exists(__DIR__."/$var.class.php")) {
			
			// If we don't HAVE the library already (eg, we may be __called numerous
			// times..)
			if (!class_exists($var))
				include "$var.class.php";

			// Now, we may have paramters (__call), or we may not..
			if (isset($args[1])) {
				// Currently we're only autoloading with one parameter.
				$this->$var = new $var($this, $args[1][0]);
			} else {
				$this->$var = new $var($this);
			}
			return $this->$var;
		}
		// Extra smarts in here later for loading stuff from modules?
		throw new Exception("Unable to find the Class $var to load");
	}

	private function listDefaultLibraries() {
		return array("Config");
	}
}
