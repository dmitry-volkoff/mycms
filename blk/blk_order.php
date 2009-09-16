<?php
/**
 * Online Order
 *
 * @author vdb
 * @version CVS: $Id$
 */
class blk_order extends block
{
	/**
	 * Constructor (php4)
	 */
	function blk_order()
	{
		$this->__construct();
	}

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $default_lang, $tr;

		$this->template = get_class($this) .'.html'; 
		if (! is_readable('./tpl/'.$this->template)) 
		{ 
			$this->template = 'default.html'; 
		}	
	} // end constructor


	/**
	 * Output to browser
	 */
	function output()
	{
		global $tpl, $db, $tr, $default_lang, $p;
		$out = '';

		$p->addScript('jquery.js');
		$p->addScriptDeclaration(trim(implode(' ', file('./orderform.js'))));
		//$p->addScript('orderform.js');

		ob_start();

		require_once "HTML/QuickForm.php"; 
		//require_once "default_form_renderer.php";

		$selected_form_id = $post_form_id = '';
		// unset POST/GET frm_id, otherwise we're in trouble - all forms fields 'frm_id' 
		// assigned to the same value.
		if (isset($_REQUEST['frm_id']) && ($_REQUEST['frm_id']))
		{
			$selected_form_id = $_REQUEST['frm_id'];
			unset($_REQUEST['frm_id']);
			if (isset($_POST['frm_id'])) { $post_form_id = $_POST['frm_id']; }
			unset($_POST['frm_id']);
			unset($_GET['frm_id']);
		}

		// select the form to submit
		$form = new HTML_QuickForm('frmsel', 'post', '', '_self', array('class' => 'forms'));
		$form->removeAttribute('name');        // XHTML compliance
		$form->registerRule('integer', 'regex', '/^[0-9]+$/');
		$form->registerRule('even', 'regex', '/^[\d]*[02468]$/');
		$form->registerRule('multiple1000', 'regex', '/^[\d]*000$/');


		$products = array(
			'x'	 => '-- Выберите тип продукции --',
			'd0' => 'блокнот',
			'd1' => 'брошюра (каталог) на пружине (WIRO)',
			'd2' => 'брошюра (каталог) на скрепке',
			'd3' => 'буклет (лифлет)',
			'd4' => 'визитки',
			'd5' => 'календарь ежеквартальный',
			'd6' => 'календарь карманный',
			'd7' => 'календарь настольный (домик)',
			'd8' => 'книга (журнал, каталог) в мягком переплете (КБС)',
			'd9' => 'газета',
			'd10'=> 'папка',
			'd11'=> 'сумка (бумажная)',
			'd12'=> 'книга в твердом переплете (7Б, 7БЦ)',
			'd13'=> 'листовка (флаер, бланк, билет, купон)',
			'd14'=> 'этикетки (наклейки)',
		);

		/**
		 * add params for simple elements as follows:
		 * 'var' => 'label'
		 *
		 * OR
		 *
		 * add params for groups and single elements like this:
		 * 'var' = array($elementtype, $elementName, $elementLabel, array $options, $ruleName),
		 *
		 */
		$params = array (
			'd0' => array (
				'format' 		=> 
				array(
					array('text', 'format1', 'формат (длина x ширина мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format2', 'формат (длина x ширина мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'tip_skrep' 		=> 
				array(
					array('select', 'tip_skrep', 'тип скрепления', array('0' => 'пружина WIRO', '1' => 'клеевое')), 
				),
				'nal_obl' 		=> 
				array(
					array('checkbox', 'nal_obl', 'наличие обложки'), 
				),
				'nal_podl'		=> 
				array(
					array('checkbox', 'nal_podl', 'наличие подложки'), 
				),
				'bum_kart_obl'		=> 'бумага (картон) на обложку',
				'plot_bum_kart_obl'	=> 
				array(
					array('text', 'plot_bum_kart_obl', 'плотность бумаги (картона) обложки', array('size' => '5'), array('integer', 'required')), 
				),
				'kras_obl'		=> 
				array(
					array('text', 'kras_obl1', 'красочность обложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_obl2', 'красочность обложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'bum_kart_podl'		=> 'бумага (картон) на подложку',
				'plot_bum_kart_podl'	=> 
				array(
					array('text', 'plot_bum_kart_podl', 'плотность бумаги (картона) подложки', array('size' => '5'), array('integer', 'required')), 
				),
				'kras_podl'		=> 
				array(
					array('text', 'kras_podl1', 'красочность подложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_podl2', 'красочность подложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'bum_blok'		=> 'бумага на блок',
				'plot_bum_blok'		=> 
				array(
					array('text', 'plot_bum_blok', 'плотность бумаги на блок', array('size' => '5'), array('integer', 'required')), 
				),
				'kras_blok'		=> 
				array(
					array('text', 'kras_blok1', 'красочность блока', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_blok2', 'красочность блока', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
			),
			'd1' => array (
				'format' 		=> 
				array(
					array('text', 'format1', 'формат (в сложенном виде)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format2', 'формат (в сложенном виде)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'kol_polos_blok'	=> 
				array(
					array('text', 'kol_polos_blok', 'кол-во полос в блоке', array('size' => '5'), 'even'), 			
				),
				'kras_blok'		=> 
				array(
					array('text', 'kras_blok1', 'красочность блока', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_blok2', 'красочность блока', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'plot_bum_blok'		=> 
				array(
					array('text', 'plot_bum_blok', 'плотность бумаги блока', array('size' => '5'), array('integer', 'required')), 
				),
				'nal_obl' 		=> 
				array(
					array('checkbox', 'nal_obl', 'наличие обложки'), 
				),
				'kras_obl'		=> 
				array(
					array('text', 'kras_obl1', 'красочность обложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_obl2', 'красочность обложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'bum_kart_obl'		=> 'бумага (картон) на обложку',
				'plot_bum_kart_obl'	=> 
				array(
					array('text', 'plot_bum_kart_obl', 'плотность бумаги (картона) обложки', array('size' => '5'), array('integer', 'required')), 
				),
			),
			'd2' => array (
				'format' 		=> 
				array(
					array('text', 'format1', 'формат (в сложенном виде)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format2', 'формат (в сложенном виде)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'kol_polos_blok'	=> 
				array(
					array('text', 'kol_polos_blok', 'кол-во полос в блоке', array('size' => '5'), 'even'), 			
				),
				'kras_blok'		=> 
				array(
					array('text', 'kras_blok1', 'красочность блока', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_blok2', 'красочность блока', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'plot_bum_blok'		=> 
				array(
					array('text', 'plot_bum_blok', 'плотность бумаги блока', array('size' => '5'), array('integer', 'required')), 
				),
				'nal_obl' 		=> 
				array(
					array('checkbox', 'nal_obl', 'наличие обложки'), 
				),
				'kras_obl'		=> 
				array(
					array('text', 'kras_obl1', 'красочность обложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_obl2', 'красочность обложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'bum_kart_obl'		=> 'бумага (картон) на обложку',
				'plot_bum_kart_obl'	=> 
				array(
					array('text', 'plot_bum_kart_obl', 'плотность бумаги (картона) обложки', array('size' => '5'), array('integer', 'required')), 
				),
			),
			'd3' => array (
				'format' 		=> 
				array(
					array('text', 'format1', 'формат (в развернутом виде)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format2', 'формат (в развернутом виде)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'kol_falcev'		=> 
				array(
					array('text', 'kol_polos_blok', 'кол-во фальцев', array('size' => '5'), 'integer'), 			
				),
				'kras'			=> 
				array(
					array('text', 'kras1', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras2', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'plot_bum'		=> 
				array(
					array('text', 'plot_bum', 'плотность бумаги', array('size' => '5'), array('integer', 'required')), 
				),
			),
			'd4' => array (
				'bum'			=> 'наименование бумаги',
				'kras'			=> 
				array(
					array('text', 'kras1', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras2', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'plot_bum'		=> 
				array(
					array('text', 'plot_bum', 'плотность бумаги', array('size' => '5'), array('integer', 'required')), 
				),
				'kol_vid'		=> 
				array(
					array('text', 'kol_vid', 'количество видов', array('size' => '5'), 'integer'), 			
				),
			),
			'd5' => array (
				'format' 		=> 
				array(
					array('text', 'format1', 'формат кадендарной сетки (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format2', 'формат кадендарной сетки (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'format_podl'		=> 
				array(
					array('text', 'format_podl1', 'формат подложки (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format_podl2', 'формат подложки (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'kras_podl'		=> 
				array(
					array('text', 'kras_podl', 'красочность подложки', array('size' => '5'), array('integer', 'required')), 
				),		
				'format_top'		=> 
				array(
					array('text', 'format_top1', 'формат топа (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format_top2', 'формат топа (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'kras_top'		=> 
				array(
					array('text', 'kras_top', 'красочность топа', array('size' => '5'), array('integer', 'required')), 
				),
			),
			'd6' => array (
				'kras' 			=> 
				array(
					array('text', 'kras1', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras2', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'tip_zapech_mat'	=> 'тип запечатываемого материала',
				'plot_zapech_mat'	=> 
				array(
					array('text', 'plot_zapech_mat', 'плотность запечатываемого материала', array('size' => '5'), array('integer', 'required')), 
				),
			),
			'd7' => array (
				'kol_list_blok'		=> 
				array(
					array('select', 'kol_list_blok', 'кол-во листов в блоке', array('0' => 'без листов', '1' => '6', '2' => '12')), 
				),
				'kras' 			=> 
				array(
					array('text', 'kras', 'красочность блока', array('size' => '5'), array('integer', 'required')), 
				),		
				'plot_bum_blok'		=> 
				array(
					array('text', 'plot_bum_blok', 'плотность бумаги блока', array('size' => '5'), array('integer', 'required')), 
				),
				'kras_podl'		=> 
				array(
					array('text', 'kras_podl', 'красочность подложки', array('size' => '5'), array('integer', 'required')), 
				),		
				'bum_kart_podl'		=> 'бумага (картон) подложки',
				'plot_bum_kart_podl'	=> 
				array(
					array('text', 'plot_bum_kart_podl', 'плотность бумаги (картона) подложки', array('size' => '5'), array('integer', 'required')),
				),
			),
			'd8' => array (
				'format' 		=> 
				array(
					array('text', 'format1', 'формат (в сложенном виде)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format2', 'формат (в сложенном виде)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'kol_polos_blok'	=> 
				array(
					array('text', 'kol_polos_blok', 'кол-во полос в блоке', array('size' => '5'), 'even'), 			
				),			
				'kras_blok'		=> 
				array(
					array('text', 'kras_blok1', 'красочность блока', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_blok2', 'красочность блока', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'plot_bum_blok'		=> 
				array(
					array('text', 'plot_bum_blok', 'плотность бумаги блока', array('size' => '5'), array('integer', 'required')), 
				),
				'kras_obl'		=> 
				array(
					array('text', 'kras_obl1', 'красочность обложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_obl2', 'красочность обложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'bum_kart_obl'		=> 'бумага (картон) обложки',
				'plot_bum_kart_obl'	=> 
				array(
					array('text', 'plot_bum_kart_obl', 'плотность бумаги (картона) обложки', array('size' => '5'), array('integer', 'required')),
				),
			),
			'd9' => array (
				'format' 		=> 
				array(
					array('text', 'format1', 'формат (в сложенном виде)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format2', 'формат (в сложенном виде)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'kol_polos'		=> 
				array(
					array('text', 'kol_polos', 'кол-во полос', array('size' => '5'), 'integer'), 
				),			
				'kras'			=> 
				array(
					array('text', 'kras1', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras2', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'bum'			=> 'бумага',
				'plot_bum'		=> 
				array(
					array('text', 'plot_bum', 'плотность бумаги', array('size' => '5'), array('integer', 'required')), 
				),
			),
			'd10' => array (
				'format' 		=> 'формат',
				'tip_papki'		=> 
				array(
					array('select', 'tip_papki', 'тип папки', array('0' => 'цельновырубная', '1' => 'с приклейным клапаном')), 
				),			
				'kras'			=> 
				array(
					array('text', 'kras1', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras2', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'bum_kart'		=> 'бумага (картон)',
				'plot_bum_kart'		=> 
				array(
					array('text', 'plot_bum_kart', 'плотность бумаги (картона)', array('size' => '5'), array('integer', 'required')),
				),
				'dop_obr'		=> 
				array(
					array('select', 'dop_obr', 'дополнительная обработка', array('0' => 'без обработки', '1' => 'УФ-лак', '2' => 'ламинация')), 
				),			
			),
			'd11' => array (
				'format' 		=> 
				array(
					array('text', 'format1', 'размер (ШхВхГ)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format2', 'размер (ШхВхГ)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format3', 'размер (ШхВхГ)(мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'kras'			=> 
				array(
					array('text', 'kras', 'красочность', array('size' => '5'), array('integer', 'required')), 
				),		
				'bum'			=> 'бумага',
				'plot_bum'		=> 
				array(
					array('text', 'plot_bum', 'плотность бумаги', array('size' => '5'), array('integer', 'required')), 
				),
				'dop_obr'		=> 
				array(
					array('select', 'dop_obr', 'дополнительная обработка', array('0' => 'без обработки', '1' => 'УФ-лак', '2' => 'ламинация')), 
				),			
				'ust_luver'		=> 
				array(
					array('checkbox', 'ust_luver', 'установка люверсов'), 
				),
			),
			'd12' => array (
				'format' 		=> 
				array(
					array('text', 'format1', 'формат (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format2', 'формат (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'tip_pereplet'		=> 
				array(
					array('select', 'tip_pereplet', 'тип переплета', array('0' => '7Б', '1' => '7БЦ')), 
				),			
				'kol_polos_blok'	=> 
				array(
					array('text', 'kol_polos_blok', 'кол-во полос в блоке', array('size' => '5'), 'integer'),
				),			
				'kras_blok'		=> 
				array(
					array('text', 'kras_blok1', 'красочность блока', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_blok2', 'красочность блока', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'bum_blok'		=> 'бумага блока',
				'plot_bum_blok'		=> 
				array(
					array('text', 'plot_bum_blok', 'плотность бумаги блока', array('size' => '5'), array('integer', 'required')), 
				),
				'kras_obl'		=> 
				array(
					array('text', 'kras_obl1', 'красочность обложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_obl2', 'красочность обложки', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'plot_bum_forzats'	=> 
				array(
					array('text', 'plot_bum_forzats', 'плотность бумаги форзацев', array('size' => '5'), array('integer', 'required')), 
				),
				'kras_forzats'		=> 
				array(
					array('text', 'kras_forzats1', 'красочность форзацев', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras_forzats2', 'красочность форзацев', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
			),
			'd13' => array (
				'format' 		=> 
				array(
					array('text', 'format1', 'формат (в развернутом виде) (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format2', 'формат (в развернутом виде) (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'kras'			=> 
				array(
					array('text', 'kras1', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
					array('text', 'kras2', 'красочность', array('size' => '5'), array('integer', 'required'), '&nbsp;+&nbsp;'), 
				),			
				'plot_bum'		=> 
				array(
					array('text', 'plot_bum', 'плотность бумаги', array('size' => '5'), array('integer', 'required')), 
				),
				'dop_obr'		=> 
				array(
					array('select', 'dop_obr', 'дополнительная обработка', array('0' => 'без обработки', '1' => 'биговка', '2' => 'перфорация')), 
				),
				'offset_lak' 		=> 
				array(
					array('checkbox', 'offset_lak', 'офсетный лак'), 
				),
			),
			'd14' => array (
				'format' 		=> 
				array(
					array('text', 'format1', 'формат (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
					array('text', 'format2', 'формат (мм)', array('size' => '5'), array('integer', 'required'), '&nbsp;x&nbsp;'), 
				),
				'kras'			=> 
				array(
					array('text', 'kras', 'красочность', array('size' => '5'), array('integer', 'required')), 
				),		
				'bum_kart'		=> 'бумага (картон)',
				'plot_bum_kart'		=> 
				array(
					array('text', 'plot_bum_kart', 'плотность бумаги (картона)', array('size' => '5'), array('integer', 'required')), 
				),
			),
		);

		// rendering with all default options
		$form->addElement('header', null, 'Форма заказа');
		$form->addElement('select', 'divsel', 'Вид изделия: ', $products, array('id' => 'divsel'));//, $attrs);
		if ($selected_form_id)
		{
			$form->setdefaults(array('divsel' => $selected_form_id));
		}


		//echo "<pre>\n";
		//var_dump($_POST);
		//echo "</pre>\n";


		$form->display();


		echo '<div id="forms">';
		for ($i = 0; $i < count($products); $i++)
		{

			$fid = 'd' . $i;
			$display = ($selected_form_id === $fid) ? '' : 'none';
			$form =& new HTML_QuickForm($fid, 'post', null, null, array('class' => 'forms', 'style'=>'display:'.$display.';')); 
			$form->removeAttribute('name');        // XHTML compliance
			//$form =& new HTML_QuickForm($fid, 'post'); 
			//$form->addElement('header', 'hdrQuickformtest', 'Online Order Form '.$i); 

			if (isset($params[$fid]))
			{
				foreach($params[$fid] as $var => $label)
				{
					if (is_array($label))
					{
						$group = array();
						$rule = array();
						foreach($label as $avar => $aval)
						{
							if ($aval[0] === 'text')
							{
								$aval[3]['id'] = $aval[1] .'_'.$i;
								$group[] =& $form->createElement($aval[0], $aval[1], $aval[2].':', $aval[3]);
							} else if ($aval[0] === 'select') {
								$group[] =& $form->createElement($aval[0], $aval[1], $aval[2].':', $aval[3], array('id' => $aval[1] .'_'.$i));
							} else if ($aval[0] === 'checkbox') {
								$group[] =& $form->createElement($aval[0], $aval[1], $aval[2].':', null, array('id' => $aval[1] .'_'.$i));
							}
							if (isset($aval[4]))
							{
								foreach((array)$aval[4] as $key => $val)
								{
									$rule[$aval[1]][] = array($tr->t('Value must be '.$val), $val);
								}
							}
							$form->applyFilter($aval[1], 'trim');
						}
						if (count($label) > 1) // group
						{
							if (! isset($aval[5])) { $aval[5] = '&nbsp;'; }
							$form->addGroup($group, $var .'_'.$i, $aval[2].':', $aval[5]);
							$form->applyFilter($var, 'trim');

							if (count($rule)) // validation rule
							{
								$form->addGroupRule($var.'_'.$i, $rule);

								//$form->addRule('grp'.'_'.$i, 'error: '.$aval[3], $aval[3], null, 'server');
							}
						} else { // end group
							$form->addElement($group[0]);
							if (count($rule)) // validation rule
							{
								//$form->addGroupRule('grp'.'_'.$i, $rule);
								foreach((array)$aval[4] as $key => $val)
								{
									$form->addRule($var, $tr->t('Value must be '.$val), $val);
								}
							}
						}
					} else {
						$form->addElement('text', $var, $label .': ', array('id' => $var .'_'.$i));
						$form->applyFilter($var, 'trim');
					}
				}
			}
			$form->addElement('text', 'tirage', 'Тираж: ');
			if ($fid === 'd6')
			{
				$form->addRule('tirage', $tr->t('Value must be multiple of 1000'), 'multiple1000');
			} else {
				$form->addRule('tirage', $tr->t('Value must be integer'), 'integer');
			}
			$form->addRule('tirage', $tr->t('Value must be required'), 'required');
			$form->addElement('text', 'srok_isp', 'Срок исполнения: ');
			$form->addElement('textarea', 'special_notes', 'Особые пожелания или требования: ');
			$form->addElement('html', '<tr><td>&nbsp;</td><td>&nbsp;</td></tr><br />');
			$form->addElement('text', 'customer_name', 'Наименование организации заказчика: ', array('size' => 40));
			$form->addRule('customer_name', $tr->t('Value must be required'), 'required');
			$form->addElement('text', 'customer_phone', 'Контактный телефон: ', array('size' => 40));
			$form->addRule('customer_phone', $tr->t('Value must be required'), 'required');
			$form->addElement('text', 'customer_contact', 'Контактное лицо: ', array('size' => 40));
			$form->addRule('customer_contact', $tr->t('Value must be required'), 'required');
			$form->addElement('hidden', 'frm_id', $fid);

			$form->setRequiredNote('<span style="font-size:80%; color:#ff0000;">*</span><span style="font-size:80%;">'.$tr->t('denotes required field').'</span>');

			$form_valid = false;
			if ($selected_form_id && ($selected_form_id == $fid) && $form->validate()) 
			{
				//echo "<pre>\n";
				//var_dump($form->exportValues());
				//echo "</pre>\n";
				$form->freeze();
				$form_valid = true;
			} else {
				// show buttons on non-valide forms
				$form->addElement('submit', 'btnSubmit', 'Отправить заказ', array('id' => 'btnSubmit' .'_'. $i)); 
			}
			//$form->display();
			$out = $form->toHtml();
			echo $out;
			if (($post_form_id == $fid) && $form_valid) 
			{

				echo '<hr />';
				echo '<div style="color:red;font-weight:bold;">Спасибо, ваш заказ принят!</div>';

				// send mail to admin
				$email_to = SITE_EMAIL;
				//$email_to = 'vdb@mail.ru';
				$email_message = '<html><body><h3>'.$products[$fid].'<h3>'.$out .'</body></html>';
				$email_from = SITE_EMAIL;
				$email_from_name = 'Форма заказа на печать';
				$email_subject = 'Новый заказ с сайта '.SITE_FQDN.': '. $products[$fid];
				$email_header = "Content-Type: text/html; charset=windows-1251";
				$email_subject_enc = "=?windows-1251?b?" . base64_encode($email_subject) . "?=";
				$email_header_add = 'From: "'."=?windows-1251?b?" . base64_encode($email_from_name) . "?=" . '" <'.$email_from.'>'."\r\n".$email_header ."\r\n";

				mail($email_to, $email_subject_enc, $email_message, $email_header_add);
			}
		}
		//echo "\n". '<div id="form_footer" style="font-size:1.2em;margin-top:1em;">';
		//echo 'Если Вам не ясны какие-то позиции в заполняемой форме, Вы всегда можете позвонить по телефонам 689-04-86; 689-73-68, и уточнить необходимую информацию, или сформировать заявку вместе с нашим сотрудником.';
		//echo '</div>';
		echo '</div>';


		$output = ob_get_contents();

		$this->content->content = ob_get_contents();
		ob_end_clean();

		// output
		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($this->content);
	}    
}
?>
