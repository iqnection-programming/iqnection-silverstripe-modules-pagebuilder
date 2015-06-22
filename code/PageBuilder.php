<?php
	
	class SiteConfig_PageBuilder extends DataExtension {
	
		private static $db = array(
			'PageStructure' => 'Text'
		);
	
		public function updateCMSFields(FieldList $fields) {
			if (Permission::check('ADMIN'))
			{
				$fields->addFieldToTab("Root.PageBuilder", new LiteralField("Instructions", "
					<p class=\"helpText\">
						Enter your list of initial pages and their page types.  If you do not include a page type, it will default to \"Page\".<br />
						<br />
						To make a child page, begin the line with a symbol \"~\" for each level.  For example, second level pages would look like this:<br />
						<br />
						About Us<br />~Our Services<br />~Our History
						<br />
						<br />
						To set the page type, use a bar \"|\" between the page name and page type, like this:<br />
						Home|HomePage<br /><br />
						Existing pages at the same level will not be duplicated, if the page exists at the same level, it will not be changed, but children may be created
					</p>
				"));
				$fields->addFieldToTab("Root.PageBuilder", new TextAreaField("PageStructure", "Page Structure"));
			}
		}
		
		public function onBeforeWrite()
		{
			parent::onBeforeWrite();
			
			if ($new_pages = $this->owner->PageStructure)
			{
				$new_pages = explode("\n", $new_pages);
				$curr_level_page = array();
				$level_sequence = array(
					1 => 0,
					2 => 0,
					3 => 0,
					4 => 0,
				);
				
				foreach ($new_pages as $page)
				{
					$page_class = false;
					
					if (strpos($page, "|") !== false)
						list($page_name, $page_class) = explode("|", trim($page));
					else
						$page_name = trim($page);
						
					$level = 1;
					while (preg_match("/^\~/", $page_name))
					{
						$page_name = substr($page_name, 1);
						$level++;
					}
					$level_sequence[$level] += 10;
					$parentID = 0;
					if ($level > 1 && $curr_level_page[($level-1)])
					{
						$parent = $curr_level_page[($level-1)];
						$parentID = $parent->ID;
					}

					// see if the page exists
					if (!$new_page = DataObject::get_one('SiteTree',"Title = '".$page_name."' AND ParentID = ".$parentID))
					{
						if ($page_class && $page_class != "Page" && class_exists($page_class))
						{
							$new_page = new $page_class();
						}
						else
						{
							$new_page = new Page();
						}
						$new_page->Title = $page_name;
						$new_page->Content = '<p>Donec tristique sagittis volutpat. Donec vitae fringilla enim. Vivamus ut velit consectetur, suscipit enim eu, vestibulum ipsum. Morbi tincidunt arcu et nunc consequat dictum. Donec venenatis dolor ac dolor malesuada, non fringilla diam tristique. Duis sit amet semper velit. Vivamus porttitor lectus sed erat interdum, at facilisis urna accumsan. Nunc sit amet sapien et nibh pharetra suscipit at ac urna. Praesent semper eros a mi adipiscing vehicula. Donec pharetra aliquet porta.</p>';
						$new_page->Status = 'Published';
						$new_page->Sort = $level_sequence[$level];
						if ($parentID)
						{
							$new_page->ParentID = $parentID;
						}
						$new_page->write();
						$new_page->doPublish();
						$new_page->flushCache();
					}
					else
					{
						// update the position if the page already exists
						$new_page->Sort = $level_sequence[$level];
						$new_page->write();
					}
					$curr_level_page[$level] = $new_page;
				}
			}
			
			$this->owner->PageStructure = "";
		}
	}