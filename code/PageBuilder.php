<?php
	
	class SiteConfig_PageBuilder extends DataExtension {
	
		private static $db = array(
			'PageStructure' => 'Text'
		);
	
		public function updateCMSFields(FieldList $fields) {
			$fields->addFieldToTab("Root.PageBuilder", new LiteralField("Instructions", "
				<p class=\"helpText\">
					Enter your list of initial pages and their page types.  If you do not include a page type, it will default to \"Page\".<br />
					<br />
					To make a child page, begin the line with a dash for each level.  For example, second level pages would look like this:<br />
					<br />
					About Us<br />-Our Services<br />-Our History
				</p>
			"));
			$fields->addFieldToTab("Root.PageBuilder", new TextAreaField("PageStructure", "Page Structure"));
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
					
					if (strpos($page, "/") !== false)
						list($page_name, $page_class) = explode("/", trim($page));
					else
						$page_name = trim($page);
						
					$level = 1;
					while (preg_match("/^-/", $page_name))
					{
						$page_name = substr($page_name, 1);
						$level++;
					}
					$level_sequence[$level] += 10;
					
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
					if ($level > 1 && $curr_level_page[($level-1)])
					{
						$parent = $curr_level_page[($level-1)];
						$new_page->ParentID = $parent->ID;
					}
					$new_page->write();
					$new_page->publish('Stage', 'Live');
					$new_page->flushCache();
					
					$curr_level_page[$level] = $new_page;
				}
			}
			
			$this->owner->PageStructure = "";
		}
	}