<?php

abstract class ncore_WidgetBaseViewController extends WP_Widget
{
	protected function adminName()
	{
		return '';
	}

	protected function userDescription()
	{
		return _ncore( 'Another %s widget.', ncore_api()->pluginDisplayName() );
	}

	protected function userHeadline()
	{
		return '';
	}

	protected function haveUserContent()
	{
		return true;
	}

	protected function renderHeadline()
	{
		return $this->userHeadline();
	}

	protected function widgetId()
	{
		return $this->htmlSetting( 'widget_id' );
	}

	protected function view()
	{
		ob_start();
		$html = trim( ob_get_clean() );

		if (!$html)
		{
			return;
		}

		echo $this->htmlSetting( 'before_widget' );

		$title = $this->renderHeadline();
		if ($title)
		{
			echo $this->htmlSetting( 'before_title' ), $title, $this->htmlSetting( 'after_title' );
		}

		echo $html;

		echo $this->htmlSetting( 'after_widget' );
	}


	final public function widgetRender( $widget, $args, $instance )
	{
		$this->setWidget( $widget );

		$this->setSettings( $instance );

		$this->html_settings = $args;

		$this->dispatch();
	}

	final public function widgetUpdate( $widget, $new_instance, $old_instance )
	{
		$this->setWidget( $widget );

		return $this->validateSettings( $new_instance, $old_instance );
	}

	final public function widgetTitle()
	{
		$title = $this->api->pluginDisplayName();

		$hl = $this->adminName();
		if ($hl)
		{
			$title .= " - $hl";
		}

		return $title;
	}

	final public function widgetDescription()
	{
		return ncore_paragraphs( $this->userDescription() );
	}

	final public function widgetIdBase()
	{
		$class = get_class( $this );
		return $class;
	}

	private $widget;
	private $html_settings = array();

	private function htmlSetting( $key, $default='' )
	{
		return ncore_retrieve( $this->html_settings, $key, $default );
	}

	private function setWidget( $widget )
	{
		$this->widget = $widget;
	}


}

class ncore_Widget extends WP_Widget
{
	function __construct() {

		$controller = $this->controller();

		$title = $controller->widgetTitle();
		$id_base = $controller->widgetIdBase();

		$widget_options = array();
		$widget_options[ 'description' ] = $controller->widgetDescription();

		$controll_options = array();

		parent::__construct( $id_base, $title, $widget_options, $controll_options );
	}

	function widget( $args, $instance ) {
		echo $this->controller()->widgetRender( $this, $args, $instance );
	}

	function update( $new_instance, $old_instance ) {
		return $this->controller()->widgetUpdate( $this, $new_instance, $old_instance );
	}

	function form( $instance ) {
		echo $this->controller()->widgetAdminForm( $this, $instance );
	}

	function ncore_get_field_name( $field_name )
	{
		return $this->get_field_name( $field_name );
	}

	function ncore_get_field_id( $field_name )
	{
		return $this->get_field_id( $field_name );
	}

	static function createWidget( $controller )
	{
		$instance_no = self::$instanceNo++;

		$class = "ncore_Widget_$instance_no";
		if (!class_exists($class))
		{
			trigger_error( 'You ran out of widget dummy classes. Add more classes ncore_Widget_N in file '.__FILE__);
			return;
		}

		self::$controllers[ $instance_no ] = $controller;

		register_widget( $class );
	}

	protected static $controllers = array();

	private static $instanceNo = 1;

	private $controller=false;
	private function controller()
	{
		if (!$this->controller)
		{
			list( $ncore, $Widget, $instance_no ) = explode( '_', get_class($this) );

			$this->controller = self::$controllers[ $instance_no ];
		}

		return $this->controller;
	}
}

class ncore_Widget_1 extends ncore_Widget
{
	// empty
}

class ncore_Widget_2 extends ncore_Widget
{
	// empty
}

class ncore_Widget_3 extends ncore_Widget
{
	// empty
}

class ncore_Widget_4 extends ncore_Widget
{
	// empty
}

class ncore_Widget_5 extends ncore_Widget
{
	// empty
}

