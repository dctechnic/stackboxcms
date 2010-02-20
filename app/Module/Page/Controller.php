<?php
/**
 * Page controller - sets up whole page for display
 */
class Module_Page_Controller extends Cx_Module_Controller
{
	protected $_file = __FILE__;
	
	
	/**
	 * @method GET
	 */
	public function indexAction($request)
	{
		return $this->viewUrl($request->url);
	}
	
	
	/**
	 * View page by URL
	 */
	public function viewUrl($url)
	{
		$cx = $this->cx;
		$request = $cx->request();
		
		// Ensure page exists
		$mapper = $this->mapper();
		$page = $mapper->getPageByUrl($url);
		if(!$page) {
			$pageUrl = $mapper->formatPageUrl($url);
			if($pageUrl == '/') {
				// Create new page for the homepage automatically if it does not exist
				$page = $mapper->get();
				$page->title = "Home";
				$page->url = $pageUrl;
				$page->date_created = date($mapper->adapter()->dateTimeFormat());
				$page->date_modified = $page->date_created;
				if(!$mapper->save($page)) {
					throw new Cx_Exception_FileNotFound("Unable to automatically create homepage at '" . $pageUrl . "' - Please check data source permissions");
				}
			} else {
				throw new Cx_Exception_FileNotFound("Page not found: '" . $pageUrl . "'");
			}
		}
		
		// Single module call?
		// @todo Check against matched route name instead of general request params (? - may restict query string params from being used)
		if($request->module_name && $request->module_id && $request->module_action) {
			$moduleId = (int) $request->module_id;
			$moduleName = $request->module_name;
			$moduleAction = $request->module_action;
			
			// TESTING
			$cx->dump($request->params());
			
			if($moduleId == 0) {
				// Get new module entity, no ID supplied
				// @todo Possibly restrict callable action with ID of '0' to 'new', etc. because other functions may depend on saved and valid module record
				$module = $mapper->get();
			} else {
				// Module belongs to current page
				$module = $page->modules->where(array('module_id' => $moduleId));
			}
			
			// Dispatch to single module
			$moduleResponse = $cx->dispatch($moduleName, $moduleAction, array($request, $page, $module));
			
			// Return content immediately, currently not wrapped in template
			return $moduleResponse;
		}
		
		// Load page template
		$activeTheme = ($page->theme) ? $page->theme : $cx->config('cx.default.theme');
		$activeTemplate = ($page->template) ? $page->template : $cx->config('cx.default.theme_template');
		$themeUrl = $cx->config('cx.url_themes') . $activeTheme . '/';
		$template = new Module_Page_Template($activeTemplate);
		$template->format($request->format);
		$template->path($cx->config('cx.path_themes') . $activeTheme . '/');
		$template->parse();
		
		// Template Region Defaults
		$regionModules = array();
		foreach($template->regions() as $regionName => $regionData) {
			$regionModules[$regionName] = $regionData['content'];
		}
		
		// Modules
		foreach($page->modules as $module) {
			// Loop over modules, building content for each region
			$moduleResponse = $cx->dispatch($module->name, 'indexAction', array($request, $page, $module));
			if(!is_array($regionModules[$module->region])) {
				$regionModules[$module->region] = array();
			}
			$regionModules[$module->region][] = $this->regionModuleFormat($request, $module, $moduleResponse);
		}
		
		// Replace region content
		$cx->trigger('module_page_regions', array(&$regionModules));
		foreach($regionModules as $region => $modules) {
			if(is_array($modules)) {
				// Array = Region has modules
				$regionContent = implode("\n", $modules);
			} else {
				// Use default content between tags in template (no other content)
				$regionContent = (string) $modules;
			}
			$template->replaceRegion($region, $this->regionFormat($request, $region, $regionContent));
		}
		
		// Replace template tags
		$tags = $page->toArray();
		$cx->trigger('module_page_tags', array(&$tags));
		foreach($tags as $tagName => $tagValue) {
			$template->replaceTag($tagName, $tagValue);
		}
		
		// Template string content
		$template->clean(); // Remove all unmatched tokens
		$templateContent = $template->content();
		
		// Admin stuff for HTML format
		$userIsAdmin = true; // @todo Implement the user authentication stuff...
		if($template->format() == 'html') {
			// Add admin stuff to the page
			// Admin toolbar, javascript, styles, etc.
			if($userIsAdmin) {
				$templateHeadContent = '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.1/jquery.min.js"></script>' . "\n";
				$templateHeadContent .= '<script type="text/javascript" src="' . $this->cx->config('cx.url_assets') . 'scripts/jquery.tools.min.js"></script>' . "\n";
				$templateHeadContent .= '<script type="text/javascript" src="' . $this->cx->config('cx.url_assets_admin') . 'scripts/cx_admin.js"></script>' . "\n";
				$templateHeadContent .= '<link type="text/css" href="' . $this->cx->config('cx.url_assets_admin') . 'styles/cx_admin.css" rel="stylesheet" />' . "\n";
				$templateContent = str_replace("</head>", $templateHeadContent . "</head>", $templateContent);
				$templateBodyContent = $this->view('_adminBar');
				$templateContent = str_replace("</body>", $templateBodyContent . "\n</body>", $templateContent);
			}
			
			// Prepend asset path to beginning of elements
			$templateContent = preg_replace("/<link(.*?)href=\"@([^\"|:]+)\"([^>]*>)/i", "<link$1href=\"".$themeUrl."$2\"$3", $templateContent);
			$templateContent = preg_replace("/<script(.*?)src=\"@([^\"|:]+)\"([^>]*>)/i", "<script$1src=\"".$themeUrl."$2\"$3", $templateContent);
		}
		return $templateContent;
	}
	
	
	/**
	 * @method GET
	 */
	public function newAction($request)
	{
		$pageUrl = $this->cx->url('page', array('url' => '/'));
		return $this->formView()->method('post')->action($pageUrl);
	}
	
	
	/**
	 * @method GET
	 */
	public function editAction($request)
	{
		$cx = $this->cx;
		
		// Ensure page exists
		$mapper = $this->mapper();
		$page = $mapper->getPageByUrl($request->url);
		if(!$page) {
			throw new Cx_Exception_FileNotFound("Page not found: '" . $request->url . "'");
		}
		
		
		
		return $this->formView();
	}
	
	
	/**
	 * Create a new resource with the given parameters
	 * @method POST
	 */
	public function postMethod($request)
	{
		$mapper = $this->mapper();
		$entity = $mapper->get()->data($request->post());
		if($mapper->save($entity)) {
			$pageUrl = $this->cx->url('page', array('url' => $entity->url));
			if($request->format == 'html') {
				return $this->cx->redirect($pageUrl);
			} else {
				return $this->cx->resource($entity)->status(201)->location($pageUrl);
			}
		} else {
			$this->cx->response(400); // Don't have time to properly handle this at the moment
			return $this->formView()->errors($mapper->errors());
		}
	}
	
	
	/**
	 * @method DELETE
	 */
	public function deleteMethod($request)
	{
		// Ensure page exists
		$page = $this->mapper()->getPageByUrl($request->url);
		if(!$page) {
			throw new Cx_Exception_FileNotFound("Page not found: '" . $this->mapper()->formatPageUrl($url) . "'");
		}
		
		$this->mapper()->delete($page);
	}
	
	
	/**
	 * Return view object for the add/edit form
	 */
	protected function formView()
	{
		$view = new Cx_View_Generic_Form($this->cx);
		$view->action("")
			->fields($this->mapper()->fields())
			->removeFields(array('id', 'date_created', 'date_modified'));
		return $view;
	}
	
	
	
	/**
	 * Format region return content for display on page response
	 */
	protected function regionFormat($request, $regionName, $regionContent)
	{
		if('html' == $request->format) {
			$content = '<div id="cx_region_' . $regionName . '" class="cx_region">' . $regionContent . '</div>';
		}
		return $content;
	}
	
	
	/**
	 * Format module return content for display on page response
	 */
	protected function regionModuleFormat($request, $module, $moduleResponse)
	{
		$content = "";
		if(false !== $moduleResponse) {
			if('html' == $request->format) {
				$content = '<div id="cx_module_' . $module->id . '" class="cx_module cx_module_' . $module->name . '">' . $moduleResponse . '</div>';
			}
		}
		return $content;
	}
}