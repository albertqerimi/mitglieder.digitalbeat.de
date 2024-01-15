<?php

abstract class ncore_TableRendererBaseTable
{
    /**
     * ncore_TableRendererBaseTable constructor.
     *
     * @param ncore_TableRendererLib $parent
     * @param array $settings
     */
	public function __construct( $parent, $settings ) {

		$this->api = $parent->api();

		$this->bulk_actions = ncore_retrieve( $settings, 'bulk_actions', array() );

		$this->settings = $settings;

		$this->api->load->helper( 'html' );

		$this->odd_row_css  = 'alternate';
		$this->even_row_css = '';


	}

	public function add( $renderer )
	{
		$this->renderers[] = $renderer ;
	}

	public function view()
	{
		$this->loadTableRows();

		$sysdir = $this->api->sysDir();

		$path = "$sysdir/view/table.php";

        /** @noinspection PhpUnusedLocalVariableInspection */
        $table = $this;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $rows = $this->rows;

		$data = $this->getViewData();
		extract( $data );

		$this->row_no = $this->paginator->firstRowNo();

        /** @noinspection PhpIncludeInspection */
        require $path;
	}

	public function api()
	{
		return $this->api;
	}

	public function renderHeader()
	{
		$this->_renderTH();
	}

	public function renderFooter()
	{
		$this->_renderTH();
	}

	public function showFooter()
	{
		$have_footer = $this->getCount() >= 8;
		return $have_footer;
	}

	public function renderTopActions( $have_search, $is_bottom = false )
	{
		$actions = array();

		$bulk_actions = $this->renderBulkActions($is_bottom);
		if ($bulk_actions)
		{
			$action = new stdClass();
			$action->html = $bulk_actions;
			$action->css = 'dm-table-toolbar-actions';
			$actions[] = $action;
		}

		$pagination = $this->paginator->render( $have_search );
		if ($pagination)
		{
			$action = new stdClass();
			$action->html = $pagination;
			$action->css = 'dm-table-toolbar-pagination';
			$actions[] = $action;
		}

		return $actions;
	}

	public function renderBottomActions()
	{
		return $this->renderTopActions( $have_search = false, $is_bottom = true );
	}

	public function renderRow( $row )
	{
		$row->row_no = $this->row_no++;

		$css = $this->is_odd_row
			   ? $this->odd_row_css
			   : $this->even_row_css;

		$row_css_column = $this->setting( 'row_css_column' );
		if ($row_css_column)
		{
			$css .= ' ' . ncore_retrieve( $row, $row_css_column );
		}

		$css_attr = ncore_attribute( 'class', $css );

		$this->is_odd_row = !$this->is_odd_row;

        $same_cell_html = array();
        $same_cell_css  = array();

        $same_cell_seperator = '<br />';

		echo "<tr $css_attr>";
		foreach ($this->renderers as $one)
		{
            $content = $one->render( $row );
            $css     = $one->cellCss();

            $is_in_cell_with_next = $one->isInSameCellAsNext();
            if ($is_in_cell_with_next) {
                $same_cell_html[] = $content;
                $same_cell_css[]  = $css;
                continue;
            }

            if ($same_cell_html) {
                $content = implode( $same_cell_seperator, $same_cell_html ) . $same_cell_seperator . $content;
                $css     = trim( implode( ' ', $same_cell_css ) . ' ' . $css );

                $same_cell_html = array();
                $same_cell_css  = array();
            }


			$css_attr = ncore_attribute( 'class', $css );

			echo "<td $css_attr>$content</td>";
		}
		echo "</tr>";
	}

	public function renderNoRowsMessage()
	{
		$message = $this->noRowsMessage();

        $col_span = count( $this->renderers );

		echo "<td colspan='$col_span'><p class='ncore_no_items'>$message</p></td>";
	}

	public function renderViewLinks()
	{
		$metas = $this->setting( 'views', array() );

		if (count($metas) <= 1)
		{
			return array();
		}

		$current_view = ncore_retrieve( $this->currentView(), 'view' );

		$links = array();

		$current_url = ncore_currentUrl();
		$current_url = ncore_removeArgs( $current_url, array( 'n', 'view', 'action' ), $arg_sep='&' );

		foreach ($metas as $one)
		{
			$view = $one['view'];
			$label = $one['label'];

			$count      = $this->getViewRowCount( $one );
            $is_current = $current_view == $view;

            $hide = !$count && !$is_current;
			if ($hide)
			{
				continue;
			}



			$link_css = $is_current
					  ? 'current'
					  : '';
			$link_css_attr = ncore_attribute( 'class', $link_css );

			$url = ncore_addArgs( $current_url, array( 'view' => $view ));


			$label .= " <span class='count'>($count)</span>";

			$css = $view;
			$link = "<a href='$url' $link_css_attr>$label</a>";

			$suffix = ' | ';

			$links[] = [
			    'css' => $css,
                'link' => $link,
                'suffix' => $suffix,
            ];
		}

		if ($links)
		{
			$last_index = count($links)-1;
			$links[ $last_index ]['suffix'] = '';
		}

		return $links;
	}


	public function searchLabel()
	{
		$label = $this->setting( 'search_label', _ncore( 'Search' ) );
		return $label;
	}

	public function searchFor()
	{
		return ncore_retrieve( $_GET, 'search' );
	}

	public function haveSearch()
	{
		foreach ($this->renderers as $one)
		{
			$search_type = $one->searchType();
			if ($search_type)
			{
				return true;
			}
		}

		return false;
	}

	public function getCount()
	{
		return $this->rowCount();
	}

	//
	// protected
	//
	protected $api;
	protected $renderers = array();
	protected $settings = array();

	abstract protected function getViewRowCount( $view_meta );

	abstract protected function rowCount();

	abstract protected function getRows( $limit, $order_by );

	protected function getViewData()
	{
		$table_css_attr = ncore_attribute( 'class', $this->tableCssClasses() );

		$form_action = ncore_currentUrl();
		$search_label = $this->searchLabel();
		$have_search = $this->haveSearch();
		$search_value = $this->searchFor();

		$top_actions = $this->renderTopActions( $have_search );
		$bottom_actions = $this->renderBottomActions();

		$views = $this->renderViewLinks();

        list( $form_action, $query_string ) = ncore_retrieveList( '?', $form_action, 2, $exact_size=true );

        @parse_str( $query_string, $keep_get_vars );
        if (empty($keep_get_vars)) {
            $keep_get_vars = array();
        }
        else
        {
            unset( $keep_get_vars['search'] );
            unset( $keep_get_vars['n'] );
        }

		$data = compact( 'table_css_attr', 'form_action', 'have_search', 'search_label', 'search_value', 'keep_get_vars', 'top_actions', 'bottom_actions', 'views' );

		return $data;
	}

	protected function defaultSorting()
	{
		$default_sorting = $this->setting( 'default_sorting' );
		if (is_array($default_sorting))
		{
			$order_by = ncore_retrieve( $default_sorting, 0 );
			$order_dir = strtolower( ncore_retrieve( $default_sorting, 1, 'asc' ) );
		}
		elseif ($default_sorting)
		{
			$order_by = $default_sorting;
			$order_dir = 'asc';
		}
        else
        {
            $order_by  = 'id';
            $order_dir = 'asc';
        }

		if ($order_by)
		{
			return array( $order_by, $order_dir );
		}

		foreach ($this->renderers as $one )
		{
			list( $order_by, $order_dir ) = $one->sorting();

			if ($order_by)
			{
				  return array( $order_by, $order_dir );
			}
		}

		return array( $order_by='', $order_dir );
	}

	/** @var ncore_TableRendererPaginator */
	private $paginator;
	/** @var ncore_TableRendererSorting */
	private $sorting;
	private function loadTableRows()
	{
		$this->api->load->helper( 'url' );

		$row_count = $this->rowCount();

		require_once dirname(__FILE__).'/class/paginator.php';
		$current_url = ncore_currentUrl();
		$current_url = ncore_removeArgs( $current_url, array( 'n', 'by', 'up' ), $arg_sep='&' );

		require_once dirname(__FILE__).'/class/sorting.php';
		list( $order_by, $order_dir ) = $this->defaultSorting();
		$this->sorting = new ncore_TableRendererSorting( $this->api, $current_url, $order_by, $order_dir );

        $sort_args = $this->sorting->getUrlArgs();
        $pagi_url = ncore_addArgs( $current_url, $sort_args, $arg_sep='&' );
        $this->paginator = new ncore_TableRendererPaginator( $this->api, $pagi_url, $row_count, $this->settings );


		$limit =  $this->paginator->sqlLimit();
		$order_by = $this->sorting->sqlOrderBy();

		$this->rows = $this->getRows( $limit, $order_by );
	}

	protected function setting( $key, $default='' )
	{
		return ncore_retrieve( $this->settings, $key, $default );
	}


    protected  function currentView()
	{
		if (isset($this->currentView))
		{
			return $this->currentView;
		}

        $metas = $this->setting( 'views', array() );

		if (!$metas)
		{
			$this->currentView = array();
            return $this->currentView;
		}

        $this->currentView=$metas[0];

		$selected_view = ncore_retrieve( $_REQUEST, 'view', '' );
		if ($selected_view)
		{
			foreach ($metas as $meta)
		    {
			    $view = $meta['view'];

			    $is_selected = $view === $selected_view;
			    if ($is_selected)
			    {
				    $this->currentView=$meta;
                    break;
			    }
		    }
        }

		return $this->currentView;
	}


	//
	// private
	//
	private $rows = array();
	private $bulk_actions = array();


	private $is_odd_row = true;
	private $odd_row_css = 'odd';
	private $even_row_css = 'even';
	private $row_no = 1;

	private function _renderTH()
	{
		$sorting_css = 'sorting-indicator';

        $same_cell_html = array();
        $same_cell_css  = array();

        $same_cell_seperator = '<br />';

		foreach ($this->renderers as $one)
		{
            $class = $one->headerCss();
			$label = $one->label();

            $this->sorting->header( $one, $class, $url );

			$url = esc_url( $url );

			$headline = $url
					  ? "<a href='$url'><span>$label</span><span class='$sorting_css'></span></a>"
					  : $label;


            $is_in_cell_with_next = $one->isInSameCellAsNext();
            if ($is_in_cell_with_next) {
                $same_cell_html[] = $headline;
                $same_cell_css[]  = $class;
                continue;
            }

            if ($same_cell_html) {
                $headline = implode( $same_cell_seperator, $same_cell_html ) . $same_cell_seperator . $headline;
                $class    = trim( implode( ' ', $same_cell_css ) . ' ' . $class );

                $same_cell_html = array();
                $same_cell_css  = array();
            }



            $class_attr = ncore_attribute( 'class', $class );

			echo "<th scope='col' $class_attr>$headline</th>";
		}
	}


	private $bulk_action_html = false;

	private function renderBulkActions($is_bottom = false)
	{
		if ($this->bulk_action_html !== false)
		{
			return $is_bottom ? str_replace('dm-select dm-fullwidth', 'dm-select dm-fullwidth direction-top', $this->bulk_action_html) : $this->bulk_action_html;
		}

		if (!$this->bulk_actions)
		{
			return $this->bulk_action_html='';
		}



		$current_view = ncore_retrieve( $this->currentView(), 'view' );

		$html = '';
		foreach ($this->bulk_actions as $one)
		{
			$views = ncore_retrieve( $one, 'views' );
			$is_for_me = !$views || in_array( $current_view, $views );
			if (!$is_for_me)
			{
				continue;
			}

			$url = $one['url'];
			$label = $one['label'];
			$html .= "<option value=\"$url\">$label</option>";
		}

		if (!$html)
		{
			return $this->bulk_action_html=$html;
		}

		$label = _ncore('Select action');
		$html = "
<div class='dm-input-group dm-input-dense' style='min-width: 200px'>
    <select class='dm-select dm-fullwidth'><option value=''>$label</option>$html<select>
";

		$label = _ncore( 'Apply' );

		$html .= "<input type='submit' class='dm-btn dm-btn-primary dm-input-button dm-bulk-action-button' value=\"$label\" style='min-width: 100px;' />";
		$html .= '</div>';

		$this->renderBulkActionJs();

		return $this->bulk_action_html=$html;


	}

	private function renderBulkActionJs()
	{
	    /** @var ncore_HtmlLogic $model */
		$model = $this->api->load->model( 'logic/html' );

		$js = "var ids = '';
		ncoreJQ.each( ncoreJQ('input:checked.ncore_bulk_action_id' ), function( key, checkbox )
				{
					if (ids) ids += ',';
					ids += checkbox.value;
				} );

		var select = ncoreJQ(this).siblings('.dm-select').children('select');
		var url_templ = ncoreJQ(select).val();

		if (url_templ && ids)
		{
			var url = url_templ.replace( '_ID_', ids );

			window.location.href=url;
		}
";

		$model->jsOnLoad( "ncoreJQ('input.dm-bulk-action-button').click( function() { $js; return false } )" );

	}

	private function tableCssClasses()
	{
		$area = ncore_isAdminArea()
			  ? 'ncore_admin'
			  : 'ncore_user';

		return "dm-table $area";
	}



    private function noRowsMessage()
    {
        $is_search = (bool) $this->searchFor();

        $key = $is_search
             ? 'no_hits_msg'
             : 'no_items_msg';

        $view = $this->currentView();
        $msg = $view
             ? ncore_retrieve( $view,$key )
             : '';

        if (!$msg)
        {
            $msg = $this->setting( $key, _ncore( 'No items found.' ) );
        }

        return $msg;
    }


}

