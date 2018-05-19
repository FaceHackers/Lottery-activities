<?php
require('duibaimiracle.php');
class duibaimiracle_adm extends duibaimiracle {
	private $is_pass = false;

	function __construct(){
		$this->isfront = false;
		parent::__construct();
		$this->gdata['burl'] = $this->burl.$this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'_adm/';

		$map = array('index'=> 1, 'adm_login'=> 1, 'lottery_ticket_bingo' =>1);
		if(array_key_exists($this->router->fetch_method(), $map)){
			$this->is_pass = true;
		} else {
			$acess = $this->session->userdata('acess_adm');
			if($acess!="" && $acess!=null){
				$chk = $this->libc->aes_de($acess);
				$chks = explode("_", $chk);
				if(count($chks)==2){
					$this->acc = $chks[0];
					$this->gdata["acc"] = $chks[1];
					$now = time();
					$ctime = intval($chks[1]);
					if(($now-$ctime) < 6000){
						$this->is_pass = true;
						$acess = $this->libc->aes_en($chks[0]."_".time());
						$this->session->set_userdata("acess_adm", $acess);
					}
				}
			}
		}
		if($this->is_pass==false){
			$this->obj['title'] = '帳號已登出';
			$this->obj['msg'] = '您的登入效期已過，請重新登入。';
			$this->output();
		}
	}

	public function index(){
		$this->gdata["acc"] = '';
		$this->gdata["pwd"] = '';

		$this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/login');
	}

	public function adm_login(){
		if (!$this->lib_ac->chkipt(array('acc', 'pwd'), $_POST)) {
			$this->obj['code'] = 500;
			$this->obj['title'] = '系統錯誤';
			$this->obj['msg'] = '傳入資料錯誤';
			$this->output();
		}

		$login = $this->lib_bck->login($_POST["acc"], $_POST["pwd"]);
		if(!$login){
			$this->obj['code'] = 501;
			$this->obj['title'] = '輸入錯誤';
			$this->obj['msg'] = '帳號/密碼錯誤';
			$this->output();
		}

		/* 登入員編 */
		$acess = $this->libc->aes_en($_POST['acc'].'_'.time());
		$this->session->set_userdata('acess_adm', $acess);

        /* 員工部門 */
        $dept = $this->libc->aes_en($login['e_dept'].'_'.time());
        $this->session->set_userdata('dept_adm', $dept);

		$this->obj['code'] = 100;
		$this->obj['view'] = $this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/header', true);
		$this->output();
	}

    /** 登入人員 員編 */
    private function getEmployee(){
        $acess_adm = $this->session->userdata('acess_adm');
        $editor = $this->lib_codes->aes_de($acess_adm);
        $emp_1 = explode('_', $editor);
        $emp1 = $emp_1[0];

        return $emp1;
    }

    /** 登人人員 部門 */
    private function get_adm_dept(){
        $dept_adm = $this->session->userdata('dept_adm');
        $dept = $this->lib_codes->aes_de($dept_adm);
        $emp_2 = explode('_', $dept);
        $emp2 = $emp_2[0];

        $this->gdata['dept'] = $emp2;
    }

	/* 切換頁面 */
	public function toView($page){
        $id = $this->getEmployee(); /* 員編部門 */

		$this->obj['code'] = 100;
        $this->obj['id'] = $id;
		$this->obj['page'] = $page;
		$this->obj['view'] = $this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/'.$page.'/header', true);
		$this->output();
	}

	/* 子頁面 */
	public function childView(){
		if(!isset($_POST['send'])){
			$this->obj['code'] = 404;
			$this->obj['title'] = '系統錯誤';
			$this->obj['msg'] = '傳入資料錯誤';
			$this->output();
		}

		$send = $_POST['send'];

		$unit = $send['unit'];
		$page = $send['page'];

		$url = $unit.'/'.$page;

		$this->get_ticket_date(); /* 參加會員 選擇日期 */
        $this->get_adm_dept();    /* 員編部門 */

		$this->obj['code'] = 100;
		$this->obj['view'] = $this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/'.$url, true);
		$this->output();
	}

    /** 上傳期數Excel */
    public function excel_periods_upload(){
        session_start();
        if(!isset($_FILES['excelFile'])){
            $this->add_error('excel_periods_upload', '404', '上傳期數設定-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        /* 讀取 套件 */
        $this->load->library('lib_excel');

        $fileAry = $_FILES['excelFile']; /* 取得上傳檔案 */
        $arr = array(
            'upl_dir' => $this->gdata['uploadfolder'].$this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/', /*  上傳路徑*/
            'folder' => 'num_set', /* 上傳檔案名稱 */
            'file_name' => 'number_periods', /* 檔案名稱 */
            'start_row' => 2, /* 起始列 */
            'start_col' => 'A' /* 起始行 */
        );
        $ExcelValue = $this->lib_excel->read_excel($fileAry, $arr); /* 取得檔案內容 */

        $_SESSION['updata'] =  urlencode(json_encode($ExcelValue)); /* 把內容存到 session */
        $this->obj['code'] = 100;
        $this->output();
    }

    /** 上傳Excel 顯示期數清單 */
    public function upload_view_num(){
        session_start();
        $updata = json_decode(urldecode($_SESSION['updata']), true); /* 取得 檔案內容 資料 */
        session_write_close(); /* 避免等前一個頁面執行完畢，才能執行下一個頁面的情況 */

        $list = array();
        foreach ($updata as $key => $val) {
            $temp = array();

            $temp['number_periods'] = trim($val['A']); /* 期數 */
            $temp['lottery_date'] = trim($val['B']. ' '.'21:00:00'); /* 開獎日期 */
            $temp['status'] = 0; /* 可上傳 */

            /* 驗證是否有重複的期數 */
            $chk_num = $this->chk_number_periods($val['A']);
            if (empty($val['A'])) {
                $temp['status'] = 1;
            } else if (!empty($chk_num)) {
                $temp['status'] = 2;
            }

            /* 日期是否合法 */
            $chk_lottery_date = $this->isDate($val['B']);
            if (empty($val['B'])) {
                $temp['status'] = 3;
            } else if (!$chk_lottery_date) {
                $temp['status'] = 4;
            }

            /* 驗證是否有重複的開獎日期 */
            $lottery_date = $val['B']. ' '.'21:00:00';
            $chk_date = $this->chk_lottery_date($lottery_date);
            if (!empty($chk_date)) {
                $temp['status'] = 5;
            }

            /* 新增的開獎日期 不能小於今天 */
            if(strtotime($lottery_date) <= time()) {
                $temp['status'] = 6;
            }

            $list[] = $temp;
        }

        $this->gdata['list'] = json_encode($list);
        $this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/number_set/upload_view_num');
    }

    /* 上傳期數 寫入資料庫 */
    public function excel_periods_success() {
        if(!isset($_POST['data'])){
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '請確認資料是否正確，再上傳!!';
            $this->output();
        }

        /** 如有已上傳資料 或 格式錯誤 會清空陣列空值 */
        $num_data = array_filter($_POST['data']);

        $editor = $this->getEmployee(); /* 取得登入資訊 */

        /* 新增期數 */
        $editor_data = array(
            'add_adm' => $editor, /* 新增人員 */
            'mod_adm' => '' /* 最後編輯人員  預設是空的 */
        );
        foreach ($num_data as $key => $val) {

            /* 檢查是否有重複寫入 期數 */
            $chk_per = $this->mod->get_by('act_evt', array(
                'act_id'  => $this->actInfo['id'],
                'param1'  => 'number_set', /* 期數設定 參數 */
                'param2' => $val['number_periods'] /* 期數 */
            ),null,'1');

            /* 檢查是否有重複寫入 開獎日期 */
            $chk_date = $this->mod->get_by('act_evt', array(
                'act_id'  => $this->actInfo['id'],
                'param1'  => 'number_set', /* 期數設定 參數 */
                'date1' => $val['lottery_date'], /* 開獎日期 */
            ),null,'1');

            if(!empty($chk_per) || !empty($chk_date)) {
                $this->obj['code'] = 404;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '請確認期數、開獎日期是否有重複!!';
                $this->output();
            }

            if(empty($chk_per) && empty($chk_date)) {
                $this->mod->add_by('act_evt',
                    array(
                        'act_id' => $this->actInfo['id'], /* 活動代碼 */
                        'param1' => 'number_set', /* 期數設定 參數 */
                        'param2' => $val['number_periods'], /* 期數 */
                        'date1' => $val['lottery_date'], /* 開獎日期 */
                        'status1' => '0',  /* 是否有key 號碼 1=已key 0=未key*/
                        'descr2' => json_encode($editor_data) /* 新增人員 */
                    ));
            }
        }

        $this->obj['code'] = 100;
        $this->obj['title'] = '上傳成功';
        $this->obj['msg'] = '期數已成功上傳';
        $this->output();
    }

    /* 下載資料型態 */
    public function downloadExcel_type() {
        if(!isset($_POST['get_data'])) {
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '目前無會員資料';
            $this->output();
        }

        /* 資料 */
        $get_data = $_POST['get_data'];

        /* 判斷檔案名稱 */
        $get_type = $_POST['type'];

        if(!isset($get_type)) {
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '檔案名稱錯誤';
            $this->output();
        }

        $this->downloadExcel($get_data, $get_type);
    }

    /**
     * 下載檔案
     * 投注記錄
     * 存款紀錄
     * 簽到記錄
     * 對換紀錄
     * 對獎紀錄
     * @param $get_data array
     * @param $get_type type
     */
    public function downloadExcel($get_data, $get_type) {
        if(empty($get_data) || empty($get_type)) return false;

        $this->load->library('lib_excel');
        switch ($get_type) {
            case 'voucher_inquiry':
                $name = '投注记录';

                /* 標題 */
                $title = array(
                    'order_time' => '投注日期(美東)',
                    'acc_deposit' => '会员帐号',
                    'max_deposit' => '单日有效投注量',
                    'no_use_ticket' => '可获得数量',
                    'use_ticket' => '已兑换数量',
                    'expired_num' => '剩馀数量',
                    'effective_date' => '有效期限(美東)'
                );

                $file = $this->lib_excel->download(2, $get_data, $title, 'Excel5', null, true);
                $this->obj['file'] = $file;
                $this->obj['fileName'] = ''.$name.'會員名單';
                $this->obj['code'] = 100;
                $this->output();

                break;
            case 'duijiang_number':
                $name = '对奖记录';

                /* 標題 */
                $title = array(
                    'turn_time' => '兌換日期(北京)',
                    'turn_acc' => '会员帐号',
                    'turn_num' => '兌換号码',
                    'special_num' => '特别码',
                    'order_per' => '開獎期数',
                    'result' => '對獎结果',
                    'give_time' => '派彩日期 (北京) '
                );

                $file = $this->lib_excel->download(2, $get_data, $title, 'Excel5', null, true);
                $this->obj['file'] = $file;
                $this->obj['fileName'] = ''.$name.'會員名單';
                $this->obj['code'] = 100;
                $this->output();

                break;
            case 'record_Grid':
                $name = '兑换记录';

                /* 標題 */
                $title = array(
                    'turn_time' => '兑换時間(北京)',
                    'turn_acc' => '会员帐号',
                    'turn_num' => '兑换数量',
                    'bingo_number' => '中奖数量',
                    'bingo_amount' => '中奖金额'
                );

                $file = $this->lib_excel->download(2, $get_data, $title, 'Excel5', null, true);
                $this->obj['file'] = $file;
                $this->obj['fileName'] = ''.$name.'會員名單';
                $this->obj['code'] = 100;
                $this->output();

                break;
            case 'deposit_Grid':
                $name = '存款纪录';

                /* 標題 */
                $title = array(
                    'acc_deposit' => '会员帐号',
                    'sin_in_day' => '连续存款天数',
                    'no_use_ticket' => '可获得数量',
                    'use_ticket' => '已兑换数量',
                    'expired_num' => '剩馀数量',
                    'effective_date' => '有效期限(美東)'
                );

                $file = $this->lib_excel->download(2, $get_data, $title, 'Excel5', null, true);
                $this->obj['file'] = $file;
                $this->obj['fileName'] = ''.$name.'會員名單';
                $this->obj['code'] = 100;
                $this->output();
                break;
            case 'sign_in_Grid':
                $name = '签到记录';

                /* 標題 */
                $title = array(
                    'acc_deposit' => '会员帐号',
                    'sin_in_day' => '连续签到天数',
                    'no_use_ticket' => '可获得数量',
                    'use_ticket' => '已兑换数量',
                    'expired_num' => '剩馀数量',
                    'effective_date' => '有效期限(美東)'
                );

                $file = $this->lib_excel->download(2, $get_data, $title, 'Excel5', null, true);
                $this->obj['file'] = $file;
                $this->obj['fileName'] = ''.$name.'會員名單';
                $this->obj['code'] = 100;
                $this->output();
                break;
            default:
                $this->obj['code'] = 404;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '傳入資料錯誤';
                $this->output();
                break;
        }
    }

	/** 期數設定 */
	public function number_set() {
        if(!isset($_POST['type'])){
            $this->add_error('number_set', '404', '期數設定-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        $editor = $this->getEmployee(); /* 取得登入資訊 */
        switch ($_POST['type']) {
            case 'qry':
                $sql = "
                    SELECT
                          `id`,
                          `param2` `number_periods`, -- 期數
                          `param5` `special_numbers`, -- 特別號
                          `descr1` `winning_numbers`, -- 開獎號碼
                          `date1` `lottery_date`,	 -- 開獎日期
                          `status1` `chk_open`, -- 確認是否有key 号碼
                          `status2` `chk_bingo`
                    FROM
                          `act_evt`
                    WHERE
                          `act_id` = ? AND 
                          `param1` = ?
                ";
                $num_list = $this->mod->select($sql, array($this->actInfo['id'], 'number_set'));

//                $out = array();
//                foreach ($num_list as $k=>$v) {
//                    $sql = "
//                            SELECT
//                                  `param2` `mem_num`, -- 兌換期數
//                                  `date1` `distribute_time` -- 派獎時間
//                            FROM
//                                  `act_evt`
//                            WHERE
//                                  `act_id` = ? AND
//                                  `param1` = ? AND
//                                  `status1` = ? AND
//                                  `param2` = ?
//                      ";
//                    $yet_list = $this->mod->select($sql, array($this->actInfo['id'], 'bingo_mem', '1', $v['number_periods']));
//
//                    $status1 = (empty($yet_list))?'0':'1';
//
//                    $data = array(
//                        'id' => $v['id'],
//                        'number_periods' => $v['number_periods'],
//                        'special_numbers' => $v['special_numbers'],
//                        'winning_numbers' => $v['winning_numbers'],
//                        'lottery_date' => $v['lottery_date'],
//                        'status1' => $status1
//                    );
//                    $out[] = $data;
//                }

                $this->obj['code'] = 100;
                $this->obj['num_list'] = $num_list;
                $this->output();
                break;
            case 'add':
                /* 後端驗證 */
                /* 未輸入 */
                if(empty($_POST['send']['number_periods']) || empty($_POST['send']['lottery_date'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '妳未輸入期數或開獎日期';
                    $this->output();
                }

                /* 驗證日期合法性 */
                $chk_lottery_date = $this->isDate($_POST['send']['lottery_date']);
                if(!$chk_lottery_date) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '開獎日期格式，錯誤!!';
                    $this->output();
                }

                /* 驗證期數 是否有重複 */
                $chk_num = $this->chk_number_periods($_POST['send']['number_periods']);
                if(!empty($chk_num)) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '期數不能重複!!';
                    $this->output();
                }
                /* 驗證期數 End ------*/

                /* 驗證開獎日期 是否有重複 */
                $lottery_date = $_POST['send']['lottery_date']. ' '.'21:00:00';
                $chk_date = $this->chk_lottery_date($lottery_date);
                if(!empty($chk_date)) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '開獎日期不能重複!!';
                    $this->output();
                } else if(strtotime($lottery_date) <= time()) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '開獎日期 須大於現在時間！!';
                    $this->output();
                }
                /* 驗證開獎日期 End ------*/

                /* 新增期數 */
                $editor_data = array(
                    'add_adm' => $editor, /* 新增人員 */
                    'mod_adm' => '' /* 最後編輯人員  預設是空的 */
                );

                $data = array(
                    'act_id' => $this->actInfo['id'], /* 活動代碼 */
                    'param1' => 'number_set', /* 期數設定 參數 */
                    'param2' => $_POST['send']['number_periods'], /* 期數 */
                    'date1' => $lottery_date, /* 開獎日期 */
                    'status1' => '0',  /* 是否有key 號碼 1=已key 0=未key*/
                    'descr2' => json_encode($editor_data) /* 新增人員 */
                );

                $code = $this->insert_data('act_evt', $data);
                if($code == 100) {
                    $this->obj['code'] = 100;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '新增期數成功';
                    $this->output();
                } else if($code == 400) {
                    $this->obj['code'] = 400;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '新增期數失敗';
                    $this->output();
                }
                break;
            case 'mod':
                if(empty($_POST['send']['number_periods']) || empty($_POST['send']['lottery_date'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '妳未輸入期數或開獎日期';
                    $this->output();
                }

                /* 驗證 開獎期數 是否有重複 */
                $sql = "
                        SELECT
                              `param2` `number_periods` -- 期數
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `param2` = ? AND NOT
                              `id` = ? 
                        LIMIT 1
		        ";
                $number_periods = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $_POST['send']['number_periods'], $_POST['id']));

                if(!empty($number_periods)) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '期數不能重複!!';
                    $this->output();
                }
                /* 驗證 開獎期數 是否有重複 END*/

                /* 驗證 開獎日期 是否有重複 */
                $lottery_date = $_POST['send']['lottery_date']. ' '.'21:00:00';
                $sql = "
                        SELECT
                              `date1` `lottery_date` -- 日期
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `date1` = ? AND NOT
                              `id` = ? 
                        LIMIT 1
		        ";
                $number_date = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $lottery_date, $_POST['id']));

                if(!empty($number_date)) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '開獎日期不能重複!!';
                    $this->output();
                }
                /* 驗證 開獎日期 是否有重複 END*/

                /**
                 * 假如當下開獎日期 一樣可以更新 只要不去更改開獎日期的話
                 * 如果有更動日期的話 就不能更改之前的日期
                 */
                $sql = "
                        SELECT
                              `date1` `lottery_date` -- 日期
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `id` = ? 
                        LIMIT 1
		        ";
                $edit_date = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $_POST['id']));

                $originData = $edit_date['0']['lottery_date'];
                /* 判斷如果修改開獎日期的話 */
                if($originData != $lottery_date) {
                    if(strtotime($lottery_date) <= time()) {
                        $this->obj['code'] = 404;
                        $this->obj['title'] = '系統提示';
                        $this->obj['msg'] = '開獎日期 須大於現在時間！!';
                        $this->output();
                    }
                }
                /* END */

                /* 如果修改的期數 會員有轉修改的期數 並一併修改期數 */
                $sql = "
                        SELECT
                              `param2` `number_periods` -- 期數
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `id` = ? 
                        LIMIT 1
		        ";
                $edit_periods = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $_POST['id']));

                $origi_periods = $edit_periods['0']['number_periods'];
                if($origi_periods != $_POST['send']['number_periods']) {
                    $this->mod->modi_by('act_evt',
                        array(
                            'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                            'param1'  => 'turn_number', /* 轉號碼參數 */
                            'param2'  =>  $origi_periods, /* 兌獎期數 */
                            'status1' => '1' /* 未開獎參數 */
                        ),
                        array(
                            'param2'  => $_POST['send']['number_periods'], /* 期數 */
                        )
                    );
                }
                /* 如果修改的期數 會員有轉修改的期數 並一併修改期數  END*/

                /* 最後編輯人員 */
                $edit_num_data = $this->edit_num_data($_POST['id']);
                foreach ($edit_num_data as $k=>$v) {
                    $add_adm = $v['add_adm']; /* 新增人員 */
                    $v['mod_adm'] = $editor;

                    $edit_data = array(
                        'add_adm' => $add_adm,
                        'mod_adm' => $editor
                    );
                }
                /* 最後編輯人員 END*/

                /* 驗證 開獎號碼 特別碼 */
                if($_POST['send']['winning_numbers'] != '' || $_POST['send']['special_numbers'] != '') {
                    $msg = $this->check_lottery_num($_POST['send']['winning_numbers'], $_POST['send']['special_numbers']);
                    if(!empty($msg)) {
                        $this->obj['code'] = 404;
                        $this->obj['title'] = '系統提示';
                        $this->obj['msg'] = $msg;
                        $this->output();
                    }
                }
                /* 驗證 開獎號碼 特別碼  End*/

                /* 修改期數  */
                $number_open = !empty($_POST['send']['winning_numbers']) && !empty($_POST['send']['special_numbers']) ? '1' : '0'; /* 是否有key 號碼 */
                $this->mod->modi_by('act_evt',
                    array(
                        'id' => $_POST['id'], /* 主鍵 */
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'param1'  => 'number_set' /* 期數參數 */
                    ),
                    array(
                        'param2'  => $_POST['send']['number_periods'], /* 期數 */
                        'param5'  => $_POST['send']['special_numbers'], /* 特別號 */
                        'descr1'  => $_POST['send']['winning_numbers'], /* 開獎號碼 */
                        'date1'   => $lottery_date, /* 開獎日期 */
                        'status1' => $number_open,
                        'descr2'  => json_encode($edit_data)
                    )
                );
                /* 修改期數 END  */

                /* 如果有key 號碼 就開始比對 */
                /*if($_POST['send']['winning_numbers'] && $_POST['send']['special_numbers']) {
                    $this->lottery_ticket_bingo($_POST['send']['number_periods'], $_POST['send']['winning_numbers'], $_POST['send']['special_numbers']);
                }*/

                $this->obj['code'] = 100;
                $this->obj['title'] = '系統提示';
                $this->obj['msg'] = '已更新成功';
                $this->output();
                break;
            default:
                $this->obj['code'] = 404;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '傳入資料錯誤';
                $this->output();
                break;
        }
    }

    /* 最後編輯期數的人員 */
    public function edit_num_data($id) {
        $sql = "
                SELECT
                      `descr2` `edit_num` -- 編輯人員
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `id` = ? 
                LIMIT 1
		        ";
        $edit_periods = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $id));

        $out_data = array();
        foreach ($edit_periods as $k=>$v) {
            $tmp = json_decode($v['edit_num'], true); /* 解析 編輯人員 字串 */

            $data['add_adm'] = $tmp['add_adm']; /* 新增人員 */
            $data['mod_adm'] = $tmp['mod_adm']; /* 編輯人員 */

            $out_data[] = $data;
        }
        return $out_data;
    }

    /**
     * 確認開獎號碼格式
     * @param  array $normal 開獎號碼
     * @param  number $special 特別號
     * @param  return
     */
    private function check_lottery_num($normal=null, $special=null) {
        /* 有非數字根逗號 */
        if (!preg_match('/^\d{2},\d{2},\d{2},\d{2},\d{2},\d{2}$/', $normal)) {
            return '開獎號碼 數字不對，只能為01~49，請重新設定！';
        }

        $num = array();
        $arr_normal = explode(',', $normal);
        if (count($arr_normal) != 6) {
            return '開獎號碼 數目不對！';
        }
        $pattern = '/([1-4]\d{1}|0[1-9])/im';
        foreach ($arr_normal as $key => $val) {
            if (!preg_match($pattern, $val)) {
                return '開獎號碼 數字不對，只能為01~49，請重新設定！';
            }
            if (!in_array($val, $num)) {
                $num[] = $val;
            }
        }
        if (!preg_match($pattern, $special)) {
            return '特別號 數字不對，只能為01~49，請重新設定！';
        }
        if (!in_array($special, $num)) {
            $num[] = $special;
        }
        if (count($num) != 7) {
            return '開獎號碼和特別號 有數字重複，請重新設定！';
        }
        return '';
    }

    /**
     * 會員查詢 投注記錄 存款紀錄 簽到記錄 對換紀錄 對獎紀錄 單個會員查詢
     * 參加會員 投注記錄 存款紀錄 簽到記錄 對換紀錄 對獎紀錄 用日期查詢
     */
    public function get_mem_list() {
        if(!isset($_POST['type'])){
            $this->add_error('get_mem_list', '404', '會員資料型態-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        switch ($_POST['type']) {
            /* 用帳號查詢 投注紀錄 */
            case 'voucher_inquiry':
                $sql = "
                        SELECT
                              `id`,
                              `account` `acc_deposit`, -- 會員帳號
                              `descr1` `remark`, -- 備註
                              `param2` `no_use_ticket`, -- 未使用彩卷
                              `amount1` `max_deposit`, -- 每日最高存款金額
                              `amount3` `ticket_num`,	 -- 獲得兌獎卷數量
                              `itime` `ticket_add_time`, -- 投注日期
                              `status1`, -- 判斷是手動新增還是排程新增
                              `date1` `order_time` -- 投注日期
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `param4` != ? AND 
                              `param4` != ?
                ";

                /* 帳號查詢 */
                if(isset($_POST['acc'])) {
                    $sql .= 'AND `account` = "'.$_POST['acc'].'"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_ticket_list = $this->mod->select($sql, array($this->actInfo['id'], 'mem_ticket', 'sin_in', 'deposit'));

                $today = date("Y-m-d",time()); /* 今天 */

                $mem_list = array();
                $num_effective = 0;
                foreach ($mem_ticket_list as $key=>$value) {
                    $sql = "
                            SELECT
                                  DISTINCT `itime` `turn_time`, -- 對換日期
                                  `account` `turn_acc`, -- 會員帳號
                                  `param2` `order_per`, -- 兌換期數
                                  `param3` `result`, -- 兌獎結果
                                  COUNT(*) `turn_num`, -- 已兌獎彩卷數量
                                  SUM(`status1` = '3') `bingo_number`, -- 中獎數量
                                  SUM(`param5`) `bingo_amount`, -- 中獎金額
                                  `status1` -- 兌獎卷狀態 
                            FROM
                                  `act_evt`
                            WHERE
                                  `act_id` = ? AND 
                                  `param1` = ? AND
                                  `param4` = ? 
                    ";
                    $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $value['id']));


                    $use_ticket = 0;
                    $bingo_num = '未兌換';
                    $bingo_amount = '未兌換';
                    $turn_time = '';
                    $expired_num = '-';
                    if(empty($mem_turn_list)) {
                        $use_ticket = 0;
                    } else {
                        foreach ($mem_turn_list as $v) {
                            $time = $this->chang_time($v['turn_time'], '-12', 'hour');
                            $use_ticket = $v['turn_num'];

                            if($v['result'] == '待开奖') {
                                $bingo_num = '待开奖';
                                $bingo_amount = '待开奖';
                                $turn_time =$time;
                            } else if($v['bingo_number'] > 0) {
                                $bingo_num = $v['bingo_number'];
                                $bingo_amount = $v['bingo_amount'];
                                $turn_time = $time;
                            }else if($v['turn_num'] == 0) {
                                $bingo_num = '未兌換';
                                $bingo_amount = '未兌換';
                            } else if($v['bingo_number'] == 0) {
                                $bingo_num = 0;
                                $bingo_amount = 0;
                                $turn_time = $time;
                            }
                        }
                    }

                    /* 如果是手動新增  每日最高存款金額 就顯示空值 */
//                    if($value['status1'] == '1') {
//                        $value['max_deposit'] = '';
//                    }

                    /* 每日有效投注  */
                    $max_deposit = ($value['max_deposit'] != '')? $max_deposit = number_format($value['max_deposit']):'';

                    /* 有效日期 */
                    $effective_date_time = $this->chang_time($value['ticket_add_time'], '+1' , 'day');
                    $effective_date = substr($effective_date_time, '0', '10');

                    /* 過期數量 */
                    $effective = $this->chang_time($value['ticket_add_time'], '+2' , 'day');
                    $effective_str = substr($effective, '0', '10');

                    $expired_date = $effective_str.' '. '12:00:00';
                    $today = date('Y-m-d H:i:s');
                    if($value['no_use_ticket'] == '0') {
                        $expired_num = '0';
                    } else if($expired_date > $today) {
                        $expired_num = '-';
                    } else if($expired_date < $today) {
                        $expired_num = $value['no_use_ticket'];
                    }

                    /* 累計有效票卷數 */
                    //$num = ($effective_date > $today)?$num_effective += (int) $value['no_use_ticket']:$num_effective;

                    $data = array(
                        'order_time' => substr($value['order_time'], 0, 10), /* 投注日期 */
                        'acc_deposit' => $value['acc_deposit'], /* 會員帳號 */
                        'max_deposit' => $max_deposit, /* 每日最高效投注 */
                        'ticket_num' => $value['ticket_num'], /* 獲得兌獎卷數量 */
                        'effective_date' => $effective_date.' '. '23:59:59', /* 有效日期 */
                        'no_use_ticket' => $value['no_use_ticket'], /* 未兌獎彩卷數量 */
                        'use_ticket' => $use_ticket, /* 已兌獎彩卷數量 */
                        'bingo_num' => $bingo_num, /* 中獎數量 */
                        'bingo_amount' => $bingo_amount, /* 中獎金額 */
                        'turn_time' => $turn_time, /* 對換日期 */
                        'expired_num' => $expired_num  /* 過期數量 */
                    );
                    $mem_list[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $mem_list;
                $this->output();
                break;
            /* 用帳號查詢 對換紀錄 */
            case 'record_Grid':
                $sql = "
                            SELECT
                                  COUNT(`itime`) `turn_num`, -- 對換日期
                                  `itime` `turn_time`, 
                                  `account` `turn_acc`, -- 會員帳號
                                  `param2` `order_per`, -- 兌換期數
                                  `param3` `result`, -- 兌獎結
                                   SUM(`status1` = '3') `bingo_number`, -- 中獎數量
                                  SUM(`param5`) `bingo_amount` -- 中獎金額
                            FROM
                                  `act_evt`
                            WHERE
                                  `act_id` = ? AND 
                                  `param1` = ? 
                           
                    ";
                /* 帳號查詢 */
                if(isset($_POST['acc'])) {
                    $sql .= 'AND `account` = "'.$_POST['acc'].'"';
                }
                $sql .= 'GROUP BY `itime`';
                $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number'));
                //print_r($mem_turn_list);die();

                $out = array();
                $bingo_number = '';
                $bingo_amount = '';
                foreach ($mem_turn_list as $k=>$v) {
                    if($v['result'] == '待开奖') {
                        $bingo_number = '待开奖';
                        $bingo_amount = '待开奖';

                    } else if($v['bingo_number'] > 0) {
                        $bingo_number = $v['bingo_number'];
                        $bingo_amount = $v['bingo_amount'];

                    }else if($v['turn_num'] == 0) {
                        $bingo_number = '未兌換';
                        $bingo_amount = '未兌換';
                    } else if($v['bingo_number'] == 0) {
                        $bingo_number = '未中獎';
                        $bingo_amount = '未中獎';
                    }

                    $data = array(
                        'turn_time' => $v['turn_time'],
                        'turn_acc' => $v['turn_acc'],
                        'result' => $v['result'],
                        'bingo_number' => $bingo_number,
                        'bingo_amount' => $bingo_amount,
                        'turn_num' => $v['turn_num']
                    );

                    if(empty($v['turn_acc'])) {
                        $out = '';
                    } else {
                        $out[] = $data;
                    }
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $out;
                $this->output();
                break;
            /* 用帳號查詢兌獎號碼 */
            case 'duijiang_number':
                $sql = "
                        SELECT
                              `account` `turn_acc`, -- 會員帳號
                              `param2` `order_per`, -- 兌換期數
                              `param3` `result`, -- 兌獎結果
                              `param5` `receive_bonus`, -- 中獎彩金
                              `descr1` `turn_num`, -- 轉出號碼
                              `descr2` `special_num`, -- 特別號
                              `itime` `turn_time`, -- 轉出時間
                              `date1` `give_time` -- 派彩時間 北京
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? 
                ";

                /* 帳號查詢 */
                if(isset($_POST['acc'])) {
                    $sql .= 'AND `account` = "'.$_POST['acc'].'"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number'));

                $turn_num = array();
                foreach ($mem_turn_list as $key=>$value) {
                    $time = '-';
                    if($value['give_time'] != '0000-00-00 00:00:00') {
                        $time = $this->chang_time($value['give_time'], '+12', 'hour');
                    }

                    $data = array(
                        'turn_acc' => $value['turn_acc'], /* 會員帳號 */
                        'order_per' => $value['order_per'], /* 兌換期數 */
                        'result' => $value['result'], /* 兌獎結果 */
                        'receive_bonus' => $value['receive_bonus'], /* 可獲彩金 */
                        'turn_num' => $value['turn_num'], /* 轉出號碼 */
                        'special_num' => $value['special_num'], /* 特別號 */
                        'turn_time' => $value['turn_time'], /* 兌換日期 */
                        'give_time' => $time /* 派彩時間 */
                    );
                    $turn_num[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $turn_num;
                $this->output();
                break;
            /* 用帳號查詢簽到記錄 */
            case 'sign_in_Grid':
                $sql = "
                        SELECT
                              `id`,
                              `account` `acc_deposit`,  -- 會員帳號
                              `param2` `no_use_ticket`, -- 未使用彩卷
                              `param3` `sin_in_day`,    -- 簽到天數
                              `amount3` `ticket_num`,	-- 獲得兌獎卷數量
                              `itime` `ticket_add_time` -- 對獎卷 新增時間
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `param4` = ? AND 
                              `param4` != ?
                      ";

                /* 帳號查詢 */
                if(isset($_POST['acc'])) {
                    $sql .= 'AND `account` = "'.$_POST['acc'].'"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_ticket_list = $this->mod->select($sql,
                    array(
                        $this->actInfo['id'], /* 活動代碼 */
                        'mem_ticket',         /* 對獎卷參數 */
                        'sin_in',             /* 簽到參數 */
                        'deposit'             /* 存款簽到參數 */
                    )
                );

                $today = date("Y-m-d",time()); /* 今天 */
                $expired = $this->chang_time($today, '-2', 'day');
                $mem_list = array();
                foreach ($mem_ticket_list as $key=>$value) {
                    $sql = "
                            SELECT
                                  DISTINCT `itime` `turn_time`,       -- 對換日期
                                  `account` `turn_acc`,               -- 會員帳號
                                  `param2` `order_per`,               -- 兌換期數
                                  `param3` `result`,                  -- 兌獎結果
                                  count(*) `turn_num`,                -- 已兌獎彩卷數量
                                  SUM(`status1` = '3') `bingo_number`,-- 中獎數量
                                  SUM(`param5`) `bingo_amount`,       -- 中獎金額
                                  `status1`                           -- 兌獎卷狀態 
                            FROM
                                  `act_evt`
                            WHERE
                                  `act_id` = ? AND 
                                  `param4` = ? 
                    ";
                    $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], $value['id']));

                    $use_ticket = 0;
                    $bingo_num = '未兌換';
                    $bingo_amount = '未兌換';
                    $turn_time = '';
                    $expired_num = '-';
                    if(empty($mem_turn_list)) {
                        $use_ticket = 0;
                    } else {
                        foreach ($mem_turn_list as $v) {
                            $time = $this->chang_time($v['turn_time'], '-12', 'hour');
                            $use_ticket = $v['turn_num'];

                            if($v['result'] == '待开奖') {
                                $bingo_num = '待开奖';
                                $bingo_amount = '待开奖';
                                $turn_time = $time;
                            } else if($v['bingo_number'] > 0) {
                                $bingo_num = $v['bingo_number'];
                                $bingo_amount = $v['bingo_amount'];
                                $turn_time = $time;
                            }else if($v['turn_num'] == 0) {
                                $bingo_num = '未兌換';
                                $bingo_amount = '未兌換';
                            } else if($v['bingo_number'] == 0) {
                                $bingo_num = 0;
                                $bingo_amount = 0;
                                $turn_time = $time;
                            }
                        }
                    }

                    /* 有效日期 美東 */
                    $effective_date_time = $this->chang_time($value['ticket_add_time'], '+1' , 'day');
                    $effective_date = substr($effective_date_time, '0', '10');

                    /* 過期數量 北京 */
                    $effective = $this->chang_time($value['ticket_add_time'], '+2' , 'day');
                    $effective_str = substr($effective, '0', '10');

                    $expired_date = $effective_str.' '. '12:00:00';
                    $today = date('Y-m-d H:i:s');

                    /* 剩餘數量 */
                    if($value['no_use_ticket'] == '0') {
                        $expired_num = '0';
                    } else if($expired_date > $today) {
                        $expired_num = '-';
                    } else if($expired_date < $today) {
                        $expired_num = $value['no_use_ticket'];
                    }

                    $data = array(
                        'acc_deposit'    => $value['acc_deposit'], /* 會員帳號 */
                        'ticket_num'     => $value['ticket_num'], /* 獲得兌獎卷數量 */
                        'sin_in_day'     => $value['sin_in_day'], /* 簽到天數 */
                        'effective_date' => $effective_date.' '. '23:59:59', /* 有效日期 */
                        'no_use_ticket'  => $value['no_use_ticket'], /* 未兌獎彩卷數量 */
                        'use_ticket'     => $use_ticket, /* 已兌獎彩卷數量 */
                        'bingo_num' => $bingo_num, /* 中獎數量 */
                        'bingo_amount' => $bingo_amount, /* 中獎金額 */
                        'turn_time' => $turn_time, /* 對換日期 */
                        'expired_num' => $expired_num /* 過期數量 */
                    );
                    $mem_list[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $mem_list;
                $this->output();
                break;
            /* 用帳號查詢存款紀錄 */
            case 'deposit_Grid':
                $sql = "
                        SELECT
                              `id`,
                              `account` `acc_deposit`,  -- 會員帳號
                              `param2` `no_use_ticket`, -- 未使用彩卷
                              `param3` `sin_in_day`,    -- 簽到天數
                              `amount3` `ticket_num`,	-- 獲得兌獎卷數量
                              `itime` `ticket_add_time` -- 對獎卷 新增時間
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `param4` = ? AND 
                              `param4` != ?
                      ";

                /* 帳號查詢 */
                if(isset($_POST['acc'])) {
                    $sql .= 'AND `account` = "'.$_POST['acc'].'"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_ticket_list = $this->mod->select($sql,
                    array(
                        $this->actInfo['id'], /* 活動代碼 */
                        'mem_ticket',         /* 對獎卷參數 */
                        'deposit',             /* 簽到參數 */
                        'sin_in'             /* 存款簽到參數 */
                    )
                );

                $today = date("Y-m-d",time()); /* 今天 */
                $expired = $this->chang_time($today, '-2', 'day');
                $mem_list = array();
                foreach ($mem_ticket_list as $key=>$value) {
                    $sql = "
                            SELECT
                                  DISTINCT `itime` `turn_time`,       -- 對換日期
                                  `account` `turn_acc`,               -- 會員帳號
                                  `param2` `order_per`,               -- 兌換期數
                                  `param3` `result`,                  -- 兌獎結果
                                  count(*) `turn_num`,                -- 已兌獎彩卷數量
                                  SUM(`status1` = '3') `bingo_number`,-- 中獎數量
                                  SUM(`param5`) `bingo_amount`,       -- 中獎金額
                                  `status1`                           -- 兌獎卷狀態 
                            FROM
                                  `act_evt`
                            WHERE
                                  `act_id` = ? AND 
                                  `param4` = ? 
                    ";
                    $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], $value['id']));

                    $use_ticket = 0;
                    $bingo_num = '未兌換';
                    $bingo_amount = '未兌換';
                    $turn_time = '';
                    $expired_num = '-';
                    if(empty($mem_turn_list)) {
                        $use_ticket = 0;
                    } else {
                        foreach ($mem_turn_list as $v) {
                            $time = $this->chang_time($v['turn_time'], '-12', 'hour');
                            $use_ticket = $v['turn_num'];

                            if($v['result'] == '待开奖') {
                                $bingo_num = '待开奖';
                                $bingo_amount = '待开奖';
                                $turn_time = $time;
                            } else if($v['bingo_number'] > 0) {
                                $bingo_num = $v['bingo_number'];
                                $bingo_amount = $v['bingo_amount'];
                                $turn_time = $time;
                            }else if($v['turn_num'] == 0) {
                                $bingo_num = '未兌換';
                                $bingo_amount = '未兌換';
                            } else if($v['bingo_number'] == 0) {
                                $bingo_num = 0;
                                $bingo_amount = 0;
                                $turn_time = $time;
                            }
                        }
                    }

                    /* 有效日期 美東 */
                    $effective_date_time = $this->chang_time($value['ticket_add_time'], '+1' , 'day');
                    $effective_date = substr($effective_date_time, '0', '10');

                    /* 過期數量 北京 */
                    $effective = $this->chang_time($value['ticket_add_time'], '+2' , 'day');
                    $effective_str = substr($effective, '0', '10');

                    $expired_date = $effective_str.' '. '12:00:00';
                    $today = date('Y-m-d H:i:s');

                    /* 剩餘數量 */
                    if($value['no_use_ticket'] == '0') {
                        $expired_num = '0';
                    } else if($expired_date > $today) {
                        $expired_num = '-';
                    } else if($expired_date < $today) {
                        $expired_num = $value['no_use_ticket'];
                    }

                    $data = array(
                        'acc_deposit'    => $value['acc_deposit'], /* 會員帳號 */
                        'ticket_num'     => $value['ticket_num'], /* 獲得兌獎卷數量 */
                        'sin_in_day'     => $value['sin_in_day'], /* 簽到天數 */
                        'effective_date' => $effective_date.' '. '23:59:59', /* 有效日期 */
                        'no_use_ticket'  => $value['no_use_ticket'], /* 未兌獎彩卷數量 */
                        'use_ticket'     => $use_ticket, /* 已兌獎彩卷數量 */
                        'bingo_num' => $bingo_num, /* 中獎數量 */
                        'bingo_amount' => $bingo_amount, /* 中獎金額 */
                        'turn_time' => $turn_time, /* 對換日期 */
                        'expired_num' => $expired_num /* 過期數量 */
                    );
                    $mem_list[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $mem_list;
                $this->output();
                break;
            /* 用日期搜尋 投注紀錄 */
            case 'voucher_inquiry_date':
                $sql = "
                        SELECT
                              `id`,
                              `account` `acc_deposit`, -- 會員帳號
                              `descr1` `remark`, -- 備註
                              `param2` `no_use_ticket`, -- 未使用彩卷
                              `amount1` `max_deposit`, -- 每日最高存款金額
                              `amount3` `ticket_num`,	 -- 獲得兌獎卷數量
                              `itime` `ticket_add_time`, -- 投注日期
                              `status1`, -- 判斷是手動新增還是排程新增
                              `date1` `order_time` -- 投注日期
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `param4` != ? AND 
                              `param4` != ?
                ";

                /* 日期查詢 */
                if(isset($_POST['ticket_date'])) {
                    $sql .= 'AND `itime` LIKE "'.$_POST['ticket_date'].'%"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_ticket_list = $this->mod->select($sql, array($this->actInfo['id'], 'mem_ticket', 'sin_in', 'deposit'));

                $today = date("Y-m-d",time()); /* 今天 */
                $expired = $this->chang_time($today, '-2', 'day');
                $mem_list = array();
                foreach ($mem_ticket_list as $key=>$value) {
                    $sql = "
                            SELECT
                                  DISTINCT `itime` `turn_time`, -- 對換日期
                                  `account` `turn_acc`, -- 會員帳號
                                  `param2` `order_per`, -- 兌換期數
                                  `param3` `result`, -- 兌獎結果
                                  count(*) `turn_num`, -- 已兌獎彩卷數量
                                  SUM(`status1` = '3') `bingo_number`, -- 中獎數量
                                  SUM(`param5`) `bingo_amount`, -- 中獎金額
                                  `status1` -- 兌獎卷狀態 
                            FROM
                                  `act_evt`
                            WHERE
                                  `act_id` = ? AND 
                                  `param4` = ? 
                    ";
                    $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], $value['id']));
                    //print_r($mem_turn_list);

                    $use_ticket = 0;
                    $bingo_num = '未兌換';
                    $bingo_amount = '未兌換';
                    $turn_time = '';
                    $expired_num = '-';
                    if(empty($mem_turn_list)) {
                        $use_ticket = 0;
                    } else {
                        foreach ($mem_turn_list as $v) {
                            $time = $this->chang_time($v['turn_time'], '-12', 'hour');
                            $use_ticket = $v['turn_num'];

                            if($v['result'] == '待开奖') {
                                $bingo_num = '待开奖';
                                $bingo_amount = '待开奖';
                                $turn_time = $time;
                            } else if($v['bingo_number'] > 0) {
                                $bingo_num = $v['bingo_number'];
                                $bingo_amount = $v['bingo_amount'];
                                $turn_time = $time;
                            }else if($v['turn_num'] == 0) {
                                $bingo_num = '未兌換';
                                $bingo_amount = '未兌換';
                            } else if($v['bingo_number'] == 0) {
                                $bingo_num = 0;
                                $bingo_amount = 0;
                                $turn_time = $time;
                            }
                        }
                    }

                    /* 如果是手動新增  每日最高存款金額 就顯示空值 */
//                    if($value['status1'] == '1') {
//                        $value['max_deposit'] = '';
//                    }

                    /* 每日最高存款金額  */
                    $max_deposit = ($value['max_deposit'] != '')? $max_deposit = number_format($value['max_deposit']):'';

                    /* 有效日期 */
                    $effective_date_time = $this->chang_time($value['ticket_add_time'], '+1' , 'day');
                    $effective_date = substr($effective_date_time, '0', '10');

                    /* 過期數量 */
                    $effective = $this->chang_time($value['ticket_add_time'], '+2' , 'day');
                    $effective_str = substr($effective, '0', '10');

                    $expired_date = $effective_str.' '. '12:00:00';
                    $today = date('Y-m-d H:i:s');
                    if($value['no_use_ticket'] == '0') {
                        $expired_num = '0';
                    } else if($expired_date > $today) {
                        $expired_num = '-';
                    } else if($expired_date < $today) {
                        $expired_num = $value['no_use_ticket'];
                    }

//                    $sql_2 = "
//                              SELECT
//                                    SUM(`param2`) AS `no_use_ticket` -- 未使用彩卷
//                              FROM
//                                    `act_evt`
//                              WHERE
//                                    `act_id`= ? AND
//                                    `param1`= ? AND
//                                    `itime` <= '".$value['ticket_add_time']."' AND
//                                    `itime` > '".$expired."' AND
//                                    `account` = '".$value['acc_deposit']."'
//                              GROUP BY
//                                    `account`";
//                    $effective_list = $this->mod->select($sql_2, array($this->actInfo['id'], 'mem_ticket'));
//                    $num_effective = !empty($effective_list)?$effective_list['0']['no_use_ticket']:0; /* 累計有效票卷數 */

                    $data = array(
                        'order_time' => substr($value['order_time'], 0, 10), /* 投注日期 */
                        'acc_deposit' => $value['acc_deposit'], /* 會員帳號 */
                        'max_deposit' => $max_deposit, /* 每日最有效 */
                        'ticket_num' => $value['ticket_num'], /* 獲得兌獎卷數量 */
                        'effective_date' => $effective_date.' '. '23:59:59', /* 有效日期 */
                        'no_use_ticket' => $value['no_use_ticket'], /* 未兌獎彩卷數量 */
                        'use_ticket' => $use_ticket, /* 已兌獎彩卷數量 */
                        'bingo_num' => $bingo_num, /* 中獎數量 */
                        'bingo_amount' => $bingo_amount, /* 中獎金額 */
                        'turn_time' => $turn_time, /* 對換日期 */
                        'expired_num' => $expired_num /* 過期數量 */
                    );
                    $mem_list[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $mem_list;
                $this->output();
                break;
            /* 用日期蒐尋 對換紀錄 */
            case 'record_Grid_date':
                $sql = "
                            SELECT
                                  COUNT(`itime`) `turn_num`, -- 對換日期
                                  `itime` `turn_time`, 
                                  `account` `turn_acc`, -- 會員帳號
                                  `param2` `order_per`, -- 兌換期數
                                  `param3` `result`, -- 兌獎結
                                   SUM(`status1` = '3') `bingo_number`, -- 中獎數量
                                  SUM(`param5`) `bingo_amount` -- 中獎金額
                            FROM
                                  `act_evt`
                            WHERE
                                  `act_id` = ? AND 
                                  `param1` = ? 
                           
                    ";
                /* 日期查詢 */
                if(isset($_POST['ticket_date'])) {
                    $sql .= 'AND `itime` LIKE "'.$_POST['ticket_date'].'%"';
                }
                $sql .= 'GROUP BY `itime`';
                $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number'));

                $out = array();
                $bingo_number = '';
                $bingo_amount = '';
                foreach ($mem_turn_list as $k=>$v) {
                    if($v['result'] == '待开奖') {
                        $bingo_number = '待开奖';
                        $bingo_amount = '待开奖';

                    } else if($v['bingo_number'] > 0) {
                        $bingo_number = $v['bingo_number'];
                        $bingo_amount = $v['bingo_amount'];

                    }else if($v['turn_num'] == 0) {
                        $bingo_number = '未兌換';
                        $bingo_amount = '未兌換';
                    } else if($v['bingo_number'] == 0) {
                        $bingo_number = '未中獎';
                        $bingo_amount = '未中獎';
                    }

                    $data = array(
                        'turn_time' => $v['turn_time'],
                        'turn_acc' => $v['turn_acc'],
                        'result' => $v['result'],
                        'bingo_number' => $bingo_number,
                        'bingo_amount' => $bingo_amount,
                        'turn_num' => $v['turn_num']
                    );

                    if(empty($v['turn_acc'])) {
                        $out = '';
                    } else {
                        $out[] = $data;
                    }
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $out;
                $this->output();
                break;
            /* 用日期搜尋 兌獎號碼 */
            case 'duijiang_number_date':
                $sql = "
                        SELECT
                              `account` `turn_acc`, -- 會員帳號
                              `param2` `order_per`, -- 兌換期數
                              `param3` `result`, -- 兌獎結果
                              `param5` `receive_bonus`, -- 中獎彩金
                              `descr1` `turn_num`, -- 轉出號碼
                              `descr2` `special_num`, -- 特別號
                              `itime` `turn_time`, -- 轉出時間
                              `date1` `give_time` -- 派彩時間 北京
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? 
                ";

                /* 日期查詢 */
                if(isset($_POST['ticket_date'])) {
                    $sql .= 'AND `itime` LIKE "'.$_POST['ticket_date'].'%"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number'));

                $turn_num = array();
                foreach ($mem_turn_list as $key=>$value) {
                    $time = '-';
                    if($value['give_time'] != '0000-00-00 00:00:00') {
                        $time = $this->chang_time($value['give_time'], '+12', 'hour');
                    }

                    $data = array(
                        'turn_acc' => $value['turn_acc'], /* 會員帳號 */
                        'order_per' => $value['order_per'], /* 兌換期數 */
                        'result' => $value['result'], /* 兌獎結果 */
                        'receive_bonus' => $value['receive_bonus'], /* 可獲彩金 */
                        'turn_num' => $value['turn_num'], /* 轉出號碼 */
                        'special_num' => $value['special_num'], /* 特別號 */
                        'turn_time' => $value['turn_time'], /* 兌換日期 */
                        'give_time' => $time /* 派彩時間 */
                    );
                    $turn_num[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $turn_num;
                $this->output();
                break;
            /* 用日期蒐尋簽到記錄*/
            case 'sign_in_Grid_date':
                $sql = "
                        SELECT
                              `id`,
                              `account` `acc_deposit`,  -- 會員帳號
                              `param2` `no_use_ticket`, -- 未使用彩卷
                              `param3` `sin_in_day`,    -- 簽到天數
                              `amount3` `ticket_num`,	-- 獲得兌獎卷數量
                              `itime` `ticket_add_time` -- 對獎卷 新增時間
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `param4` = ? AND 
                              `param4` != ?
                      ";

                /* 日期查詢 */
                if(isset($_POST['ticket_date'])) {
                    $sql .= 'AND `itime` LIKE "'.$_POST['ticket_date'].'%"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_ticket_list = $this->mod->select($sql,
                        array(
                            $this->actInfo['id'], /* 活動代碼 */
                            'mem_ticket',         /* 對獎卷參數 */
                            'sin_in',             /* 簽到參數 */
                            'deposit'             /* 存款簽到參數 */
                        )
                );

                $today = date("Y-m-d",time()); /* 今天 */
                $expired = $this->chang_time($today, '-2', 'day');
                $mem_list = array();
                foreach ($mem_ticket_list as $key=>$value) {
                    $sql = "
                            SELECT
                                  DISTINCT `itime` `turn_time`,       -- 對換日期
                                  `account` `turn_acc`,               -- 會員帳號
                                  `param2` `order_per`,               -- 兌換期數
                                  `param3` `result`,                  -- 兌獎結果
                                  count(*) `turn_num`,                -- 已兌獎彩卷數量
                                  SUM(`status1` = '3') `bingo_number`,-- 中獎數量
                                  SUM(`param5`) `bingo_amount`,       -- 中獎金額
                                  `status1`                           -- 兌獎卷狀態 
                            FROM
                                  `act_evt`
                            WHERE
                                  `act_id` = ? AND 
                                  `param4` = ? 
                    ";
                    $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], $value['id']));

                    $use_ticket = 0;
                    $bingo_num = '未兌換';
                    $bingo_amount = '未兌換';
                    $turn_time = '';
                    $expired_num = '-';
                    if(empty($mem_turn_list)) {
                        $use_ticket = 0;
                    } else {
                        foreach ($mem_turn_list as $v) {
                            $time = $this->chang_time($v['turn_time'], '-12', 'hour');
                            $use_ticket = $v['turn_num'];

                            if($v['result'] == '待开奖') {
                                $bingo_num = '待开奖';
                                $bingo_amount = '待开奖';
                                $turn_time = $time;
                            } else if($v['bingo_number'] > 0) {
                                $bingo_num = $v['bingo_number'];
                                $bingo_amount = $v['bingo_amount'];
                                $turn_time = $time;
                            }else if($v['turn_num'] == 0) {
                                $bingo_num = '未兌換';
                                $bingo_amount = '未兌換';
                            } else if($v['bingo_number'] == 0) {
                                $bingo_num = 0;
                                $bingo_amount = 0;
                                $turn_time = $time;
                            }
                        }
                    }

                    /* 有效日期 美東 */
                    $effective_date_time = $this->chang_time($value['ticket_add_time'], '+1' , 'day');
                    $effective_date = substr($effective_date_time, '0', '10');

                    /* 過期數量 北京 */
                    $effective = $this->chang_time($value['ticket_add_time'], '+2' , 'day');
                    $effective_str = substr($effective, '0', '10');

                    $expired_date = $effective_str.' '. '12:00:00';
                    $today = date('Y-m-d H:i:s');

                    /* 剩餘數量 */
                    if($value['no_use_ticket'] == '0') {
                        $expired_num = '0';
                    } else if($expired_date > $today) {
                        $expired_num = '-';
                    } else if($expired_date < $today) {
                        $expired_num = $value['no_use_ticket'];
                    }

                    $data = array(
                        'acc_deposit'    => $value['acc_deposit'], /* 會員帳號 */
                        'ticket_num'     => $value['ticket_num'], /* 獲得兌獎卷數量 */
                        'sin_in_day'     => $value['sin_in_day'], /* 簽到天數 */
                        'effective_date' => $effective_date.' '. '23:59:59', /* 有效日期 */
                        'no_use_ticket'  => $value['no_use_ticket'], /* 未兌獎彩卷數量 */
                        'use_ticket'     => $use_ticket, /* 已兌獎彩卷數量 */
                        'bingo_num' => $bingo_num, /* 中獎數量 */
                        'bingo_amount' => $bingo_amount, /* 中獎金額 */
                        'turn_time' => $turn_time, /* 對換日期 */
                        'expired_num' => $expired_num /* 過期數量 */
                    );
                    $mem_list[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $mem_list;
                $this->output();
                break;
            /* 用日期蒐尋存款紀錄 */
            case 'deposit_Grid_date':
                $sql = "
                        SELECT
                              `id`,
                              `account` `acc_deposit`,  -- 會員帳號
                              `param2` `no_use_ticket`, -- 未使用彩卷
                              `param3` `sin_in_day`,    -- 簽到天數
                              `amount3` `ticket_num`,	-- 獲得兌獎卷數量
                              `itime` `ticket_add_time` -- 對獎卷 新增時間
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `param4` = ? AND 
                              `param4` != ?
                      ";

                /* 日期查詢 */
                if(isset($_POST['ticket_date'])) {
                    $sql .= 'AND `itime` LIKE "'.$_POST['ticket_date'].'%"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_ticket_list = $this->mod->select($sql,
                    array(
                        $this->actInfo['id'], /* 活動代碼 */
                        'mem_ticket',         /* 對獎卷參數 */
                        'deposit',             /* 簽到參數 */
                        'sin_in'             /* 存款簽到參數 */
                    )
                );

                $today = date("Y-m-d",time()); /* 今天 */
                $expired = $this->chang_time($today, '-2', 'day');
                $mem_list = array();
                foreach ($mem_ticket_list as $key=>$value) {
                    $sql = "
                            SELECT
                                  DISTINCT `itime` `turn_time`,       -- 對換日期
                                  `account` `turn_acc`,               -- 會員帳號
                                  `param2` `order_per`,               -- 兌換期數
                                  `param3` `result`,                  -- 兌獎結果
                                  count(*) `turn_num`,                -- 已兌獎彩卷數量
                                  SUM(`status1` = '3') `bingo_number`,-- 中獎數量
                                  SUM(`param5`) `bingo_amount`,       -- 中獎金額
                                  `status1`                           -- 兌獎卷狀態 
                            FROM
                                  `act_evt`
                            WHERE
                                  `act_id` = ? AND 
                                  `param4` = ? 
                    ";
                    $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], $value['id']));

                    $use_ticket = 0;
                    $bingo_num = '未兌換';
                    $bingo_amount = '未兌換';
                    $turn_time = '';
                    $expired_num = '-';
                    if(empty($mem_turn_list)) {
                        $use_ticket = 0;
                    } else {
                        foreach ($mem_turn_list as $v) {
                            $time = $this->chang_time($v['turn_time'], '-12', 'hour');
                            $use_ticket = $v['turn_num'];

                            if($v['result'] == '待开奖') {
                                $bingo_num = '待开奖';
                                $bingo_amount = '待开奖';
                                $turn_time = $time;
                            } else if($v['bingo_number'] > 0) {
                                $bingo_num = $v['bingo_number'];
                                $bingo_amount = $v['bingo_amount'];
                                $turn_time = $time;
                            }else if($v['turn_num'] == 0) {
                                $bingo_num = '未兌換';
                                $bingo_amount = '未兌換';
                            } else if($v['bingo_number'] == 0) {
                                $bingo_num = 0;
                                $bingo_amount = 0;
                                $turn_time = $time;
                            }
                        }
                    }

                    /* 有效日期 美東 */
                    $effective_date_time = $this->chang_time($value['ticket_add_time'], '+1' , 'day');
                    $effective_date = substr($effective_date_time, '0', '10');

                    /* 過期數量 北京 */
                    $effective = $this->chang_time($value['ticket_add_time'], '+2' , 'day');
                    $effective_str = substr($effective, '0', '10');

                    $expired_date = $effective_str.' '. '12:00:00';
                    $today = date('Y-m-d H:i:s');

                    /* 剩餘數量 */
                    if($value['no_use_ticket'] == '0') {
                        $expired_num = '0';
                    } else if($expired_date > $today) {
                        $expired_num = '-';
                    } else if($expired_date < $today) {
                        $expired_num = $value['no_use_ticket'];
                    }

                    $data = array(
                        'acc_deposit'    => $value['acc_deposit'], /* 會員帳號 */
                        'ticket_num'     => $value['ticket_num'], /* 獲得兌獎卷數量 */
                        'sin_in_day'     => $value['sin_in_day'], /* 簽到天數 */
                        'effective_date' => $effective_date.' '. '23:59:59', /* 有效日期 */
                        'no_use_ticket'  => $value['no_use_ticket'], /* 未兌獎彩卷數量 */
                        'use_ticket'     => $use_ticket, /* 已兌獎彩卷數量 */
                        'bingo_num' => $bingo_num, /* 中獎數量 */
                        'bingo_amount' => $bingo_amount, /* 中獎金額 */
                        'turn_time' => $turn_time, /* 對換日期 */
                        'expired_num' => $expired_num /* 過期數量 */
                    );
                    $mem_list[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $mem_list;
                $this->output();
                break;
            default :
                $this->obj['code'] = 404;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '傳入資料錯誤';
                $this->output();
                break;
        }
    }

    /**
     * 簽到記錄表
     * 存款紀錄
     * 簽到記錄 有效投注
     */
    public function get_sin_in_list() {
        if(!isset($_POST['type'])){
            $this->add_error('get_mem_list', '404', '會員資料型態-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        switch ($_POST['type']) {
            /* 存款紀錄 */
            case 'deposit_sin_in':
                $sql = "
                        SELECT
                              `account`,                 -- 會員帳號
                              `amount3` `sin_in_day`,	 -- 簽到天數
                              `itime`,                   -- 新增時間
                              `utime`                    -- 更新時間
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? 
                ";

                $sql .= 'ORDER BY `itime` ASC';
                $mem_sin_list = $this->mod->select($sql, array($this->actInfo['id'], 'deposit'));

                $this->obj['code'] = 100;
                $this->obj['list'] = $mem_sin_list;
                $this->output();
                break;
            /* 簽到紀錄 */
            case 'effective_sign_in':
                $sql = "
                        SELECT
                              `account`,                 -- 會員帳號
                              `amount3` `sin_in_day`,	 -- 簽到天數
                              `itime`,                   -- 新增時間
                              `utime`                    -- 更新時間
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? 
                ";

                $sql .= 'ORDER BY `itime` ASC';
                $mem_sin_list = $this->mod->select($sql, array($this->actInfo['id'], 'sing_in'));

                $this->obj['code'] = 100;
                $this->obj['list'] = $mem_sin_list;
                $this->output();
                break;
            default:
                $this->obj['code'] = 404;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '傳入資料錯誤';
                $this->output();

                break;
        }
    }

    /* 抓取會員轉過的所有期數  未兌獎*/
    public function get_mem_turn_periods() {
        $sql = "
                SELECT
                      `account` `turn_acc`, -- 會員帳號
                      `param2` `order_per`, -- 兌換期數
                 FROM
                       `act_evt`
                 WHERE
                       `act_id` = ? AND 
                       `param1` = ? AND 
                       `status1` = ?
                ";
        $mem_turn_periods = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', '1'));

        $order_per = array();
        foreach ($mem_turn_periods as $k=>$v) {
            $pass_mem[$v['order_per']] = $v;
        }
        return $order_per;
    }

    /* 存款區間 */
    public function get_deposit_range() {
        if(!isset($_POST['type'])){
            $this->add_error('get_deposit_range', '404', '存款區間-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        switch ($_POST['type']) {
            case 'qry':
                /* 判斷 有沒有存款區間 資料 */
                $chk_deposit = $this->mod->get_by('act_evt',
                    array(
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'param1'  => 'deposit_range' /* 期數設定 參數 */
                    ), null, '1');

                if(empty($chk_deposit)) {
                    $this->mod->add_by('act_evt',
                        array(
                            'act_id' => $this->actInfo['id'], /* 活動代碼 */
                            'param1' => 'deposit_range', /* 存款區間 參數 */
                            'date1' => $this->actInfo['start_time'], /* 開始時間 */
                            'date2' =>  $this->actInfo['end_time']/* 結束時間 */
                        ));
                }

                /* 取得計算存款開始時間 */
                $deposit_range = $this->get_deposit_day();

                $this->obj['code'] = 100;
                $this->obj['list'] = $deposit_range;
                $this->output();
                break;
            case 'mod':
                if(empty($_POST['send']['deposit_end'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統錯誤';
                    $this->obj['msg'] = '傳入資料錯誤';
                    $this->output();
                }

                /* 驗證日期合法性 */
                $chk_deposit_end = $this->isDate($_POST['send']['deposit_end']);
                if(!$chk_deposit_end) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '結束時間格式，錯誤!!';
                    $this->output();
                }

                $this->mod->modi_by('act_evt',
                    array(
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'param1'  => 'deposit_range' /* 存款參數 */
                    ),
                    array(
                        'date2'   => $_POST['send']['deposit_end'], /* 存款結束日期 */
                    )
                );

                $this->obj['code'] = 100;
                $this->obj['title'] = '系統提示';
                $this->obj['msg'] = '已更新成功';
                $this->output();
                break;
            default:
                $this->add_error('get_deposit_range', '404', '存款區間-型態錯誤');
                $this->obj['code'] = 404;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '傳入資料錯誤';
                $this->output();
                break;
        }
    }

    /* 中獎會員資料 */
    public function get_bingo_mem_data() {
        if(!isset($_POST['type'])) {
            $this->add_error('get_bingo_mem_data', '404', '中獎會員資料型態-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        switch ($_POST['type']) {
            /* 未派獎 */
            case 'not_yet':
                $sql = "
                        SELECT
                              `account` `bingo_acc`, -- 會員帳號
                              `param2` `mem_num`, -- 兌換期數
                              `param3` `result`, -- 兌獎結果
                              `param5` `receive_bonus`, -- 可獲彩金
                              `descr1` `turn_num`, -- 轉出號碼
                              `descr2` `special_num`, -- 轉出號碼
                              `itime` `turn_time` -- 轉出時間
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `status2` = ? 
                ";
                $not_yet_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', '1'));

                $this->obj['code'] = 100;
                $this->obj['bingo_list'] = $not_yet_list;
                $this->output();
                break;
            /* 已派獎 */
            case 'yet':
                $sql = "
                        SELECT
                              `account` `bingo_acc`, -- 中獎會員帳號
                              `param2` `mem_num`, -- 兌換期數
                              `param3` `result`, -- 兌獎結果
                              `amount3` `receive_bonus`, -- 可獲彩金
                              `date1` `distribute_time`, -- 派獎時間
                              `date2` `turn_time`, -- 轉出時間
                              `descr2` `special_num`, -- 轉出號碼
                              `descr1` `turn_num` -- 轉出號碼
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND
                              `param1` = ? AND
                              `status1` = ?
                ";
                $yet_list = $this->mod->select($sql, array($this->actInfo['id'], 'bingo_mem', '1'));

                $this->obj['code'] = 100;
                $this->obj['bingo_list'] = $yet_list;
                $this->output();
                break;
        }
    }

    /**
     * 自動派獎設定
     * 更新派獎時間
     */
    public function send_award_set() {
        if(!isset($_POST['get_data'])) {
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統提示';
            $this->obj['msg'] = '目前尚無資料可派獎';
            $this->output();
        }

        $get_data = $_POST['get_data'];
        $type = $_POST['type'];

        $editor = $this->getEmployee(); /* 取得登入資訊 */

        switch ($type) {
            case 'yet_give_time':
                $this->obj['code'] = 100;
                foreach ($get_data as $k=>$v) {
                    $apply_name = $this->actInfo['name'].'-'.'期數:'.$v['mem_num'].'-'.'轉出號碼:'.$v['turn_num'].'-'.'特碼:'.$v['special_num'];
                    /* 新增 優惠通過資料  */
                    $return_id = $this->act_api('add_data',
                        array(
                            'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                            'act_name' => $this->actInfo['name'], /* 活動名稱 */
                            'account' => $v['bingo_acc'], /* 會員帳號 */
                            'apply_time' => date ("Y-m-d H:i:s"), /* 申請時間 */
                            'act_note' => $apply_name, /* 申請內容 */
                            'money' => (int) $v['receive_bonus'], /* 贈送優惠 */
                            'multiple' => (int) 3, /* 贈送打碼 */
                            'status'=> '通過' , /* 申請狀態 */
                            'remark' => '系統自動審核', /* 審核說明 */
                            'check_by' => $editor, /* 審核人員 */
                            'check_time' => date ("Y-m-d H:i:s"), /* 審核時間 */
                        ));

                    /**
                     * 新增迪拜活動優惠
                     * 活動代碼 + 自取編號 不能重覆
                     */
                    if(isset($return_id['code']) && $return_id['code'] == 100) {
                        $return = $this->act_api('addIncomeDubai',
                            array(
                                'acc' => $v['bingo_acc'], /* 會員帳號 */
                                'did' => $return_id['res']['lid'], /* 活動優惠紀錄ID */
                                'bid' => $this->actInfo['back_act_id'], /* 存入編號 */
                                'bonus' => (int) $v['receive_bonus'], /* 優惠金額 */
                                'multiple' => (int) 3, /* 贈送打碼 */
                                'ticket' => $return_id['res']['lid'], /* 存取標號 */
                                'add_by' => $editor /* 新增人員 */
                            ),
                            true);

                        /* 新增中獎會員 */
                        $this->mod->add_by('act_evt',
                            array(
                                'act_id' => $this->actInfo['id'], /* 活動代碼 */
                                'account' => $v['bingo_acc'], /* 會員帳號 */
                                'param1' => 'bingo_mem', /* 會員中獎 參數 */
                                'param2' => $v['mem_num'], /* 兌獎期數 */
                                'amount3' => $v['receive_bonus'], /* 可獲彩金 */
                                'date1' =>'', /* 派獎時間 */
                                'date2' => $v['turn_time'], /* 轉出時間 */
                                'descr1' => $v['turn_num'], /* 轉出號碼 */
                                'descr2' => $v['special_num'], /* 新增人員 */
                                'status1' => '1' /* 0 = 未派發 1= 已派發 */
                            ));

                        $this->mod->modi_by('act_evt',
                            array(
                                'act_id' => $this->actInfo['id'], /* 活動代碼 */
                                'account' => $v['bingo_acc'], /* 會員帳號 */
                                'param1' => 'turn_number', /* 會員中獎 參數 */
                                'param2' => $v['mem_num'], /* 兌獎期數 */
                                'param5' => $v['receive_bonus'], /* 可獲彩金 */
                                'itime' => $v['turn_time'], /* 轉出時間 */
                                'descr1' => $v['turn_num'], /* 轉出號碼 */
                                'descr2' => $v['special_num'], /* 特碼 */
                                'status1' => '3' /* 3 = 已中獎 */
                            ),
                            array(
                                'status2' => '2' /*  2 = 已派發獎金 */
                            )
                        );
                    } else {
                        $log = $v['bingo_acc']. 'API錯誤';
                        $this->add_error('send_award_set', '400', $log);

                        $this->obj['code'] = 400;
                    }
                }
                if ($this->obj['code'] == 400) {
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '尚有資料未更新成功!!';
                    $this->output();
                }
                $this->obj['title'] = '系統提示';
                $this->obj['msg'] = '更新自動派獎成功!!';
                $this->output();

                break;
            case 'update_give_time':
                foreach ($get_data as $key=>$val) {
                    if($val['distribute_time'] == '0000-00-00 00:00:00') {
                        $get_list = $this->get_Data_list($this->actInfo['id'], $val['bingo_acc']);
                        $apply_name = $this->actInfo['name'].'-'.'期數:'.$val['mem_num'].'-'.'轉出號碼:'.$val['turn_num'].'-'.'特碼:'.$val['special_num'];
                        foreach ($get_list as $k=>$v) {
                            if($v['act_note'] == $apply_name) {
                                $give_time = $this->chang_time($v['give_time'], '+12', 'hour');
                                /* 更新中獎會員派獎時間 */
                                $this->mod->modi_by('act_evt',
                                    array(
                                        'act_id' => $this->actInfo['id'], /* 活動代碼 */
                                        'account' => $val['bingo_acc'], /* 會員帳號 */
                                        'param1' => 'bingo_mem', /* 會員中獎 參數 */
                                        'param2' => $val['mem_num'], /* 兌獎期數 */
                                        'amount3' => $val['receive_bonus'], /* 可獲彩金 */
                                        'date2' => $val['turn_time'], /* 轉出時間 */
                                        'descr1' => $val['turn_num'], /* 轉出號碼 */
                                        'descr2' => $val['special_num'], /* 特碼 */
                                        'status1' => '1' /* 0 = 未派發 1= 已派發 */
                                    ),
                                    array(
                                        'date1' => $give_time /* 派獎時間 */
                                    )
                                );

                                /* 更新轉出號碼 派獎時間 */
                                $this->mod->modi_by('act_evt',
                                    array(
                                        'act_id' => $this->actInfo['id'], /* 活動代碼 */
                                        'account' => $val['bingo_acc'], /* 會員帳號 */
                                        'param1' => 'turn_number', /* 會員中獎 參數 */
                                        'param2' => $val['mem_num'], /* 兌獎期數 */
                                        'param5' => $val['receive_bonus'], /* 可獲彩金 */
                                        'itime' => $val['turn_time'], /* 轉出時間 */
                                        'descr1' => $val['turn_num'], /* 轉出號碼 */
                                        'descr2' => $val['special_num'], /* 特碼 */
                                        'status1' => '3', /* 3 = 已中獎 */
                                        'status2' => '2' /* 2 = 已派獎 */
                                    ),
                                    array(
                                        'date1' => $give_time /* 派獎時間 */
                                    )
                                );
                            }
                        }
                    }
                }
                $this->obj['code'] = 100;
                $this->obj['title'] = '系統提示';
                $this->obj['msg'] = '更新派獎時間成功!!';
                $this->output();
                break;
        }
    }

    /**
     * 取得 會員派獎資訊
     * @param $act_id 活動代碼
     * @param $acc 會員帳號
     * @param return array key account
     */
    public function get_Data($act_id, $acc=null) {
        $getData = $this->act_api('getData', $act_id, $acc);
        $getData_res = $getData['res'];

        $nary = array();
        foreach ($getData_res as $key=>$val) {
            $nary[$val['account']] = $val;
        }
        return $nary;
    }

    /**
     * 取得 會員派獎資訊
     * @param $act_id 活動代碼
     * @param $acc 會員帳號
     * @param return array
     */
    public function get_Data_list($act_id, $acc=null) {
        $getData = $this->act_api('getData', $act_id, $acc);
        $getData_res = $getData['res'];

        return $getData_res;
    }

    /**
     * 驗證期數 是否有重複
     * @param string $number_periods 期數
     * @param return array
     */
    public function chk_number_periods($number_periods) {
      if(empty($number_periods)) return false;

        $chk_num = $this->mod->get_by('act_evt',
            array(
                'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                'param1'  => 'number_set', /* 期數設定 參數 */
                'param2' => $number_periods /* 期數 */
            ), null, '1');
        return $chk_num;
    }

    /**
     * 驗證開獎日期 是否有重複
     * @param date $lottery_date 開獎日期
     * @param return array
     */
    public function chk_lottery_date($lottery_date) {
        if(empty($lottery_date)) return false;

        $chk_date = $this->mod->get_by('act_evt',
            array(
                'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                'param1'  => 'number_set', /* 期數設定 參數 */
                'date1' => $lottery_date /* 開獎日期 */
            ), null, '1');
        return $chk_date;
    }

    /**
     * 驗證日期合法性
     * @param date $str 日期
     * @param bool
     */
    public function isDate($str) {
        if(!preg_match("/^(\d{4})[-](\d{1,2})[-](\d{1,2})$/", $str)) return false;

        $__y = substr($str, 0, 4);
        $__m = substr($str, 5, 2);
        $__d = substr($str, 8, 2);

        return checkdate($__m, $__d, $__y);
    }
}
