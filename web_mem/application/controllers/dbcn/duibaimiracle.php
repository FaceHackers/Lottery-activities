<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
@ini_set('display_errors', 1);
class duibaimiracle extends web_mem {
	protected $isfront = true;
	private $is_pass = false;
	private $act_id = '001892';	// 活動代碼

    /**
     * 宣告單日有效投注 可獲得彩票兌獎卷
     * 滿2000 獲得 1組 以此類推 最高10組
     */
    public $ticketNum = array(
        2000  => 1,
        4000  => 2,
        6000  => 3,
        8000  => 4,
        10000 => 5,
        12000 => 6,
        14000 => 7,
        16000 => 8,
        18000 => 9,
        20000 => 10
    );

	function __construct(){
		parent::__construct();
		#若不在活動時間內則跳錯誤訊息
		if (!$this->chk_act_date($this->act_id) && $this->isfront) {
			echo '<script>
					alert("'.$this->gdata["msg"].'");
					document.location.href="'.$this->actInfo['os_link'].'";
			  	  </script>';
			die();
		}

		$this->gdata['act_id']           = $this->actInfo['id'];						// 活動代碼
		$this->gdata['act_name']         = $this->actInfo['name'];						// 活動名稱
		$this->gdata['back_act_id']      = $this->actInfo['back_act_id'];				// 優惠存入編號(迪拜用)
		$this->gdata['com_name']         = $this->actInfo['com_name'];					// 娛樂城名稱
		$this->gdata['com_id']           = $this->actInfo['com_id'];					// 娛樂城id
		$this->gdata['comp']             = $this->actInfo['comp'];						// 娛樂城縮寫(活動call以前的api用)
		$this->gdata['api_id']           = $this->actInfo['api_id'];					// call api用
		$this->gdata['api_code']         = $this->actInfo['api_code'];					// call api用
		$this->gdata['folder']           = $this->actInfo['folder'];					// 各娛樂城資料夾名
		$this->gdata['start_time']       = $this->actInfo['start_time'];				// 活動開始時間
		$this->gdata['end_time']         = $this->actInfo['end_time'];					// 活動結束時間
		$this->gdata["act_ctrl"]         = $this->actInfo['act_ctrl'];					// 活動controller名稱
		$this->gdata['act_title']        = $this->actInfo['act_title'];					// 活動title
		$this->gdata['meta_key']         = $this->actInfo['meta_key'];					// 活動keywords
		$this->gdata['meta_des']         = $this->actInfo['meta_des'];					// 活動description
		$this->gdata['google_analytics'] = $this->actInfo['google_analytics'];			// Google分析碼
		$this->gdata['os_link']          = $this->actInfo['os_link'];					// 官網連結
		$this->gdata['cs_link']          = $this->actInfo['cs_link'];					// 在線客服連結

		$this->gdata['burl'] = $this->burl.$this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/';
		$this->gdata['furl'] = $this->furl.$this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/';

		$map = array(
			'index'                     => 1, /* 前台首頁 */
			'login'                     => 1, /* 前台登入 */
			'toView'                    => 1, /* 切換頁面 */
            'mem_effective_day'         => 1, /* 更新兌獎卷數量 有效投注可獲得對應的兌獎卷 */
            'get_last_lottery'          => 1, /* 前台 倒數計時用 */
            'use_ticket'                => 1, /* 使用兌換卷數  */
            'use_mem_ticket'            => 1, /* 扣除使用卷數 */
            'get_periods_data'          => 1, /* 期數資料查詢 */
            'get_periods'               => 1, /* 期數查詢 */
            'get_ticket_date'           => 1, /* 搜尋 兌獎卷歷史紀錄 預設日期 為 活動開始跟活動結束*/
            'get_mem_history_turn_num'  => 1, /* 轉號碼歷史紀錄 顯示有轉過的日期 */
            'get_mem_turn_history'      => 1, /* 顯示轉號碼 歷史紀錄清單 */
            'get_ticket_story'          => 1, /* 顯示兌獎卷歷史 清單 */
            'get_turn_date_history'     => 1, /* 個人對獎紀錄 歷史紀錄*/
            'my_ball_num'               => 1, /* 我的彩球 */
            'update_last_term'          => 1, /* 已點中獎通知 */
            'lottery_bingo_result'      => 1, /* 自動比對開獎結果 */
            'sing_in_effective'         => 1, /* 抓取有效投注超過50 即為簽到一天*/
            'mem_deposit_sing_in'       => 1,  /* 抓取每日存款會員 派發兌獎卷數量*/
            'get_mem_sin_in' => 1
		);
		$map_class = $this->router->fetch_class();
		if ($map_class == $this->actInfo['act_ctrl']) {
			if(array_key_exists($this->router->fetch_method(), $map)){
				$this->is_pass = true;
			} else {
				$acess = $this->session->userdata('acess');
				if($acess!="" && $acess!=null){
					$chk = $this->libc->aes_de($acess);
					$chks = explode("*", $chk);
					if(count($chks)==5){
						$this->acc = $chks[3];
						$this->gdata["acc"] = $chks[3];
						$now = time();
						$ctime = intval($chks[0]);
						if(($now-$ctime) < 6000){
							$this->is_pass = true;
							$code = $now."*".$chks[1]."*".$chks[2]."*".$chks[3]."*";
							$acess = $this->libc->aes_en($code);
							$this->session->set_userdata("acess", $acess);
						}
					}
				}
			}
			if($this->is_pass==false){
				$this->output();
			}
		}
	}

	/* 首頁 */
	public function index(){
        $this->gdata['ticket_start'] = substr($this->actInfo['start_time'],0,10);
        $this->gdata['ticket_end'] = substr($this->actInfo['end_time'],0,10);

		$this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/index');
	}

	/* 切換頁面 */
	public function toView($page){
		$this->obj['code'] = 100;
		$this->obj['page'] = $page;
		$this->obj['view'] = urlencode($this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/'.$page, true));
		$this->output();
	}

	/* 會員登入 */
	public function login($dubai=false){
		if(!isset($_POST['acc'])){
			$this->obj['code'] = 404;
			$this->obj['title'] = '系统错误';
			$this->obj['msg'] = '传入资料错误';
			$this->output();
		}

		$acc = trim($_POST['acc']);
		if (!preg_match('/^[A-Za-z0-9]+$/', $acc)) {
			$this->obj['code'] = 401;
            $this->obj['title'] = '帐号错误';
            $this->obj['msg'] = '您未成为本站会员，请进行免费注册';
            $this->output();
		}

		if (!$dubai) {
			$call = 'http://misc.bcad8.com/api/query/get_mem_by_acc/BBIN/'.$this->actInfo['api_code'].'/'.$acc;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $call);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			$call_url = curl_exec($ch);
			curl_close($ch);

			$url = 'http://misc.bcad8.com/api/query/chk_acc_exist/'.$this->actInfo['api_code'].'/'.$acc;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			$get_url = curl_exec($ch);
			curl_close($ch);

			$chk_acc = json_decode($get_url, true);
		} else {
			$chk_acc = $this->dubai_api('getAcc', $acc);
		}

		if(!isset($chk_acc['code'])){
			$this->obj['code'] = 404;
			$this->obj['title'] = '系统错误';
			$this->obj['msg'] = '系統發生錯誤，請通知系統管理員！';
			$this->output();
		}

		switch($chk_acc['code']){
			case 100:
                /* 非迪拜中國 */
                if ($chk_acc['id_country'] != 1) {
                    $this->obj['code'] = 200;
                    $this->obj['title'] = '帐号错误';
                    $this->obj['msg'] = '您未成为本站会员，请进行免费注册';
                    $this->output();
                }
                $acc = $chk_acc['acc'];
                $this->mem_login_list($acc);

				break;
			case 200:
				$this->obj['code'] = 200;
				$this->obj['title'] = '帐号错误';
				$this->obj['msg'] = '您未成为本站会员，请进行免费注册';
				break;
			default:
				$this->obj['code'] = 404;
				$this->obj['title'] = '系统错误';
				$this->obj['msg'] = '系統發生錯誤，請通知系統管理員！';
				break;
		}

		$this->output();
	}

	/**
     * 會員登入顯示資訊
     * @param string $acc 會員帳號
     */
	public function mem_login_list($acc) {
        $count_ticket = $this->get_mem_tick_qry($acc); /* 剩餘數量 */
        $amount = $this->get_mem_bingo_amount($acc); /* 中獎金額 */
        $exchange_ball = $this->exchange_ball($acc); /* 聖誕彩球 */
        $get_last_term = $this->get_last_term($acc); /* 是否有中獎結果 1 = 有 0 = 無*/
        $get_today_turn_num = $this->get_today_turn_num($acc);

        $this->obj['code'] = 100;
        $this->obj['acc'] = $acc;
        $this->obj['count_ticket'] = $count_ticket;
        $this->obj['amount'] = $amount;
        $this->obj['exchange_ball'] = $exchange_ball;
        $this->obj['get_today_turn_num'] = $get_today_turn_num;
        $this->obj['get_last_term'] = $get_last_term;
        $this->output();
    }

    /**
     * 判斷今日轉球組數 是否超過 10組以上
     * @param string $acc 會員帳號
     * @param return number
     */
    public function get_today_turn_num($acc) {
        $today = date("Y-m-d") . ' ' . '00:00:00'; /* 今天 00:00:00 */
        $today_end = date("Y-m-d") . ' ' . '23:59:59'; /* 今天 23:59:59 */

        $sql = "
                SELECT
                      COUNT(`itime`) `turn_num`
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `account` = ? AND
                      `param1` = ? AND
                      `itime`
                BETWEEN  '" . $today . "' AND '" . $today_end . "'
                     
                ";
        $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], $acc, 'turn_number'));
        $num = $mem_turn_list['0']['turn_num'];

        return $num;
    }

    /* 使用兌換卷數  */
    public function use_ticket() {
        /* 判斷 如果沒有期數 */
        $today = date("Y-m-d H:i:s"); /* 今天 */

        $num_list = $this->get_last_lottery_qry($today);
        if (empty($num_list)) {
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統提示';
            $this->obj['msg'] = '目前尚无设定六合期数！';
            $this->output();
        }
        /* 判斷 如果沒有期數 End*/

        /**
         * 判斷今日是否為開獎日期
         * 檢查時間 是否在 21:00~22:00
         */
        $chk_date = $this->check_drawing_lottery();
        if (!empty($chk_date)) {
            $today = date("Y-m-d H:i:s", time()); /* 現在時間 */

            $start_stop = date("Y-m-d 21:00:00", time()); /* 停止運轉時間 開始 */
            $end_stop = date("Y-m-d 22:00:00", time()); /* 停止運轉時間 結束 */

            if ($end_stop >= $today && $start_stop <= $today) {
                $this->obj['code'] = 404;
                $this->obj['title'] = '系统提示';
                $this->obj['msg'] = '即奖开奖！彩球机停止运转！将于22:00开放！';
                $this->output();
            }
        }
        /* 檢查時間 是否在 21:00~22:00  則無法轉號碼 End */

        $acc = $_POST['acc']; /* 會員帳號 */
        //$ticket = (int)$_POST['count_ticket']; /* 兌換卷數 */
        //$number_periods = $_POST['number_periods']; /* 兌獎期數 */

        /* 判斷 今日如果有超果10組對換數量 則還有組數可對換  顯示彈窗 明日在來對換 */
        $today_num = $this->get_today_turn_num($acc);
        if($today_num == 10) {
            $this->obj['code'] = 300;
            $this->output();
        }
        /* END */

        /* 判斷是否張數足夠 */
        $count_ticket = $this->get_mem_tick_qry($acc);
        if ($count_ticket < 0) {
            $this->obj['code'] = 400;
            $this->obj['title'] = '系统提示';
            $this->obj['msg'] = '六合彩券张数不足!';
            $this->output();
        }
        /* 判斷是否張數足夠 END*/

        /* 扣掉使用票卷 lock*/
        $this->mod->select("SELECT GET_LOCK('lock', 10)");
        $isFree = $this->mod->select("SELECT IS_FREE_LOCK('lock')");
        if (!$isFree) $this->output();
        $num_last = !empty($num_list)?$num_list['0']['number_periods']:'';
        $ticket_num = ($count_ticket > 10)?10:$count_ticket; /* 判斷 如果超過10組以上 則只能轉10組*/
        $this->use_mem_ticket($acc, $ticket_num, $num_last);
        /* 扣掉使用票卷  END*/

        /* 產生號碼 */
        //$lottery_ball = array();
//        $num_last = !empty($num_list)?$num_list['0']['number_periods']:'';
//        for ($i = 0; $i < $count_ticket; $i++) {
//            $lottery_ball = $this->lottery_ball();
//            $numSpecial = array_pop($lottery_ball);
//            sort($lottery_ball);
//            $numNormal = implode(',', $lottery_ball);
//
//            $this->mod->add_by('act_evt',
//                array(
//                    'act_id' => $this->actInfo['id'], /* 活動代碼 */
//                    'account' => $acc, /* 會員帳號 */
//                    'param1' => 'turn_number', /* 會員兌換數量 參數 */
//                    'param2' => $num_last, /* 兌獎期數  */
//                    'param3' => '待开奖', /* 兌獎結果 */
//                    'param4' => $ticket_id, /* 兌獎彩球 ID */
//                    'descr1' => $numNormal, /* 兌獎號碼 */
//                    'descr2' => $numSpecial, /* 特別號 */
//                    'status1' => '1' /* 未兌獎 狀態 */
//            ));
//        }

        $count_ticket = $this->get_mem_tick_qry($acc); /* 剩下組數 */
        $exchange_ball = $this->exchange_ball($acc); /* 已對換組數 */
        $this->mod->select("SELECT RELEASE_LOCK('lock')");

        $this->obj['code'] = 100;
        $this->obj['acc'] = $acc;
        //$this->obj['lottery_ball'] = $lottery_ball;
        $this->obj['count_ticket'] = $count_ticket;
        $this->obj['exchange_ball'] = $exchange_ball;
        $this->output();
    }

    /**
     * 扣值已使用卷數
     * @param string $acc 會員帳號
     * @param number $ticket 兌獎卷數量
     * @param string $num_last 下注期數
     */
    public function use_mem_ticket($acc, $ticket, $num_last) {
        $count_ticket = $this->get_mem_tick_qry($acc);
        if ($count_ticket < $ticket) {
            $this->obj['code'] = 400;
            $this->obj['title'] = '系统提示';
            $this->obj['msg'] = '六合彩券张数不足!';
            $this->output();

        }

        $sql = "
                 SELECT
                      `id`,
                      `param2` `no_use_ticket`, -- 未使用彩卷
                      `itime` `ticket_add_time` -- 兌獎卷新增時間
                 FROM
                      `act_evt`
                 WHERE
                      `act_id` = ? AND
                      `param1` = ? AND
                      `account` = ? AND 
                      `param2` > ?
                ";

        $sql .= 'ORDER BY `id` ASC';
        $mem_ticket_list = $this->mod->select($sql, array($this->actInfo['id'], 'mem_ticket', $acc, '0'));

        $daily = 10; /* 每天上限轉10組號碼 */
        $result = $daily;

        foreach ($mem_ticket_list as $k=>$v) {
            /* 有效日期 */
            $effective_date_time = $this->chang_time($v['ticket_add_time'], '+2', 'day');
            $effective_date = substr($effective_date_time, '0', '10');

            $today = date("Y-m-d H:i:s ",time()); /* 今天 */
            $date =  $effective_date. ' '. '12:00:00';

            if($date > $today) {
                $check = $result - $v['no_use_ticket'];

                if ($check > 0) {
                    $round = $v['no_use_ticket'];
                } else {
                    $round = $result;
                }

                for ($i = 0; $i < $round; $i++) {
                    $lottery_ball = $this->lottery_ball();    /* 產生開獎號碼 */
                    $numSpecial   = array_pop($lottery_ball); /* array_pop 將陣列 最後一個元素彈出 */

                    sort($lottery_ball); /* 由小到大排序 */
                    $numNormal = implode(',', $lottery_ball); /* 解析字串 */

                    $this->mod->add_by('act_evt',
                        array(
                            'act_id' => $this->actInfo['id'], /* 活動代碼 */
                            'account' => $acc,                /* 會員帳號 */
                            'param1' => 'turn_number',        /* 會員兌換數量 參數 */
                            'param2' => $num_last,            /* 兌獎期數  */
                            'param3' => '待开奖',              /* 兌獎結果 */
                            'param4' => $v['id'],             /* 兌獎彩球 ID */
                            'descr1' => $numNormal,           /* 兌獎號碼 */
                            'descr2' => $numSpecial,          /* 特別號 */
                            'status1' => '1'                  /* 未兌獎 狀態 */
                        ));
                }

                $ticket -= $v['no_use_ticket'];

                if ($ticket < 0) {
                    $v['no_use_ticket'] = abs($ticket); /* 絕對值 */

                    /* 更新 剩餘彩票卷數 */
                    $this->mod->modi_by('act_evt',
                        array(
                            'id' => $v['id'], /* 主鍵 */
                            'act_id' => $this->actInfo['id'], /* 活動代碼 */
                            'param1' => 'mem_ticket',         /* 免費兌換卷參數 */
                            'account' => $acc                 /* 帳號 */
                        ),
                        array(
                            'param2' => $v['no_use_ticket']   /* 剩餘卷數 */
                        )
                    );

                    break;
                } else {
                    $v['no_use_ticket'] = 0;

                    /*  無彩票卷數*/
                    $this->mod->modi_by('act_evt',
                        array(
                            'id' => $v['id'],                 /* 主鍵 */
                            'act_id' => $this->actInfo['id'], /* 活動代碼 */
                            'param1' => 'mem_ticket',         /* 免費兌換卷參數 */
                            'account' => $acc                 /* 帳號 */
                        ),
                        array(
                            'param2' => 0                     /* 剩餘卷數 */
                        )
                    );
                }
                $result = $check;
            }
        }
    }

    /** 判斷今日是否開獎時間 */
    private function check_drawing_lottery() {
        $sql = "
                SELECT
                      `date1` `lottery_date`	 -- 開獎日期
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `date1` LIKE ?
          ";

        $get_list_date = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', date('Y-m-d%')));
        return $get_list_date;
    }

    /**
     * 轉號號
     * @param number $count 轉球數量
     * @param array $data
     * @param return array
     */
    private function lottery_ball($count = 7, $data = array()) {
        if (count($data) == $count) {
            return $data;
        }
        $num = sprintf('%02d', mt_rand(1, 49));
        if (!in_array($num, $data)) {
            $data[] = $num;
        }
        return $this->lottery_ball($count, $data);
    }

	/**
     * 確認會員每日有效投注 可獲的兌獎數量
     * 滿2,000 1張 以此類推 最多10張
     * 每日中午 12:00 更新
     * 每日時間計算以昨天的12:00至隔天的12:00為一天
     */
	public function mem_effective_day() {
        /* 抓取計算區間 ---------------------------------------------*/
        $deposit_day = $this->get_deposit_day(); /* 存款區間 */
        $deposit_range_start = $deposit_day[0]['deposit_start']; /* 開始時間 */
        $deposit_range_end = $deposit_day[0]['deposit_end']; /* 結束時間 */

        $deposit_range_end_add = $this->chang_time($deposit_range_end, '+1' , 'day');  /* 結束時間 再加一天 隔天會在更新最後一次 */

        $today = date("Y-m-d",time()); /* 今天 */
        $list = array();
        /* 抓取計算區間 End ------------------------------------------*/

        if($deposit_range_end_add >= $today && $deposit_range_start < $today) {
            $mem_effective_sport = $this->mem_effective_type('sport');   /* 體育 */
            $mem_effective_live = $this->mem_effective_type('live');     /* 視訊 */
            $mem_effective_slot = $this->mem_effective_type('slot');     /* 機率 */
            $mem_effective_lott = $this->mem_effective_type('lott');     /* 彩票 */
            $mem_effective_vsport = $this->mem_effective_type('vsport'); /* 虛擬體育 */

            $merge_array = $mem_effective_sport + $mem_effective_live + $mem_effective_slot + $mem_effective_lott + $mem_effective_vsport;

            foreach ($merge_array as $acc => $sum) {
                $amount = 0;

                if (isset($mem_effective_sport[$acc])) {
                    $amount = $amount + $mem_effective_sport[$acc];
                }
                if (isset($mem_effective_live[$acc])) {
                    $amount = $amount + $mem_effective_live[$acc];
                }
                if (isset($mem_effective_slot[$acc])) {
                    $amount = $amount + $mem_effective_slot[$acc];
                }
                if (isset($mem_effective_lott[$acc])) {
                    $amount = $amount + $mem_effective_lott[$acc];
                }
                if (isset($mem_effective_vsport[$acc])) {
                    $amount = $amount + $mem_effective_vsport[$acc];
                }
                if ($amount >= 2000) {
                    $num = $this->deposit_ticket(round($amount));
                    $yes_day = date("Y/m/d", strtotime('-1 day'));

                    /* 確認是否有重複新增 */
                    $today = date("Y-m-d") . ' ' . '00:00:00'; /* 今天 00:00:00 */
                    $today_end = date("Y-m-d") . ' ' . '23:59:59'; /* 今天 23:59:59 */

                    $chk_mem = $this->mod->select("SELECT
                                                        `account`
                                                   FROM
                                                        `act_evt`
                                                   WHERE
                                                        `act_id`=?  AND
                                                        `account`=? AND
                                                        `param1`= ? AND
                                                        `param4` != ? AND 
                                                        `param4` != ? AND
                                                        `itime`
                                                  BETWEEN  '" . $today . "' AND '" . $today_end . "'",
                        array($this->actInfo['id'], $acc, 'mem_ticket', 'sin_in', 'deposit')
                    );

                    if (empty($chk_mem)) {
                        $this->mod->add_by('act_evt',
                            array(
                                'act_id' => $this->actInfo['id'], /* 活動代碼 */
                                'account' => $acc, /* 會員帳號 */
                                'param1' => 'mem_ticket', /* 會員兌換數量 參數 */
                                'param2' => $num, /* 未使用卷數  */
                                'descr1' => '投注日期' . '-' . $yes_day, /* 備註 */
                                'amount1' => round($amount), /* 單日有效投注量 */
                                'amount3' => $num, /* 兌獎卷數量 */
                                'date1' => $yes_day
                        ));
                        /* 成功新增會員 */
                        $list[] = array(
                            'code' => 100,
                            'acc' => $acc,
                            'num' => $num
                        );
                    }
                }
            }
        }

        $this->obj['code'] = 100;
        $this->obj['list'] = $list;
        $this->output();
    }

    /**
     * 會員有效投注 API
     * @param string 遊戲型態 $type
     * @param return array
     */
    public function mem_effective_type($type) {
        $yes_day = date("Y/m/d", strtotime('-1 day'));
        $sport = $this->dubai_api('getBetTolByCountryDate', '1', $yes_day, $yes_day, $type, false);
        $temp = array();

        if (isset($sport['code']) && $sport['code'] == 100) {
            if (!empty($sport['list'])) {
                $sport_list = $sport['list'];
                foreach ($sport_list as $k => $v) {
                    $res = $v['recs'];
                    $amount = 0;
                    foreach ($res as $i => $value) {
                        $amount = $amount + $value['bet_real'];
                    }
                    if($amount > 0) {
                        $temp[$k] = $amount;
                    }
                }
            }
            return $temp;
        } else {
            $this->add_error('mem_effective_sport', '400', '有效投注API-錯誤');
        }
    }

    /* 會員單日有效投注 可獲得數量 */
    public function deposit_ticket($max_balance) {
        $num = 0;
        foreach ($this->ticketNum as $money => $count) {
            if ($max_balance >= $money) {
                $num = $count;
            }
        }
        return $num;
    }

    /**
     * 記錄 會員簽到天數
     * 每日有效投注 50即簽到一天
     * 2天 一組 ， 10天追加一組 、 20天追加一組 、 30天追加一組
     * 有符合資料 並派發兌獎卷
     */
    public function sing_in_effective() {
        /* 抓取計算區間 ---------------------------------------------*/
        $deposit_day = $this->get_deposit_day(); /* 存款區間 */
        $deposit_range_start = $deposit_day[0]['deposit_start']; /* 開始時間 */
        $deposit_range_end = $deposit_day[0]['deposit_end']; /* 結束時間 */

        $deposit_range_end_add = $this->chang_time($deposit_range_end, '+1' , 'day');  /* 結束時間 再加一天 隔天會在更新最後一次 */

        $today = date("Y-m-d",time()); /* 今天 */
        $list = array();
        /* 抓取計算區間 End ------------------------------------------*/

        if($deposit_range_end_add >= $today && $deposit_range_start < $today) {
            $mem_effective_sport = $this->mem_effective_type('sport'); /* 體育 */
            $mem_effective_live = $this->mem_effective_type('live'); /* 視訊 */
            $mem_effective_slot = $this->mem_effective_type('slot'); /* 機率 */
            $mem_effective_lott = $this->mem_effective_type('lott'); /* 彩票 */
            $mem_effective_vsport = $this->mem_effective_type('vsport'); /* 虛擬體育 */

            $merge_array = $mem_effective_sport + $mem_effective_live + $mem_effective_slot + $mem_effective_lott + $mem_effective_vsport;

            $mem_list = $this->get_mem_sin_in();

            foreach ($merge_array as $acc => $sum) {
                $amount = 0;

                if (isset($mem_effective_sport[$acc])) {
                    $amount = $amount + $mem_effective_sport[$acc];
                }
                if (isset($mem_effective_live[$acc])) {
                    $amount = $amount + $mem_effective_live[$acc];
                }
                if (isset($mem_effective_slot[$acc])) {
                    $amount = $amount + $mem_effective_slot[$acc];
                }
                if (isset($mem_effective_lott[$acc])) {
                    $amount = $amount + $mem_effective_lott[$acc];
                }
                if (isset($mem_effective_vsport[$acc])) {
                    $amount = $amount + $mem_effective_vsport[$acc];
                }

                if ($amount >= 50) {
                    $mem_list = $this->set_mem_sin_in($acc, $mem_list);
                }
            }

            /* 假如都是空的話 就整個資料刪除 */
            if(empty($merge_array)) {
                $this->mod->del_by('act_evt',
                    array(
                        'act_id' => $this->actInfo['id'], /* 活動代碼 */
                        'param1' => 'sing_in'             /* 簽到參數 */
                ));
            }

           foreach ($mem_list as $k=>$v) {
               $this->mod->del_by('act_evt',
                   array(
                       'act_id' => $this->actInfo['id'], /* 活動代碼 */
                       'param1' => 'sing_in',            /* 簽到參數 */
                       'account' => $k                   /* 會員帳號 */
               ));
               /* 刪除會員 */
               $list[] = array(
                   'code' => 200,
                   'del_acc' => $k
               );
           }

           $new_list = $this->get_mem_sin_in(); /* 最新的簽到會員資料 */
           foreach ($new_list as $k=>$v) {
                $mod = fmod($v['sin_num'], 2); /* 求餘數 */
                if($mod == '0') {
                    $num = 1; /* 獲得組數 */

                    /* 連續簽到 10 20 30 天 多一組*/
                    if($v['sin_num'] == '10' || $v['sin_num'] == '20' || $v['sin_num'] == '30') {
                        $num = 2;
                    }

                    /* 連續簽到天數 */
                    switch ($v['sin_num']) {
                        case 10:
                            $sin_day = 10;
                            break;
                        case 20:
                            $sin_day = 20;
                            break;
                        case 30:
                            $sin_day = 30;
                            break;
                        default:
                            $sin_day = 2;
                            break;
                    }

                    /* 確認是否有重複新增 */
                    $today = date("Y-m-d") . ' ' . '00:00:00'; /* 今天 00:00:00 */
                    $today_end = date("Y-m-d") . ' ' . '23:59:59'; /* 今天 23:59:59 */

                    $chk_mem = $this->mod->select("SELECT
                                                        `account`
                                                   FROM
                                                        `act_evt`
                                                   WHERE
                                                        `act_id` = ? AND
                                                        `account`= ? AND
                                                        `param1` = ? AND
                                                        `param4` = ? AND
                                                        `itime`
                                                  BETWEEN  '" . $today . "' AND '" . $today_end . "'",
                        array($this->actInfo['id'], $v['account'], 'mem_ticket', 'sin_in')
                    );

                    if(empty($chk_mem)) {
                        $this->mod->add_by('act_evt',
                            array(
                                'act_id' => $this->actInfo['id'], /* 活動代碼 */
                                'account' => $v['account'], /* 會員帳號 */
                                'param1' => 'mem_ticket', /* 會員兌換數量 參數 */
                                'param2' => $num, /* 未使用卷數  */
                                'param3' => $sin_day, /* 連續簽到天數 */
                                'param4' => 'sin_in', /* 簽到參數*/
                                'descr1' => '連續簽到' . '-' . $sin_day, /* 備註 */
                                'amount3' => $num /* 兌獎卷數量 */
                            ));
                        $list[] = array(
                            'code' => 100,
                            'add_acc' => $v['account']
                        );
                    }
                }
            }
        }
        $this->obj['code'] = 100;
        $this->obj['list'] = $list;
        $this->output();
    }

    /**
     * 設定簽到會員資料
     * @param string $acc 每日達到滿足有效投注50的會員
     * @param array $mem_list 會員有效投注 資料
     * @param return array
     */
    public function set_mem_sin_in($acc, $mem_list) {
        /* 確認當天是否有重複新增 */
        $today     = date("Y-m-d") . ' ' . '00:00:00'; /* 今天 00:00:00 */
        $today_end = date("Y-m-d") . ' ' . '23:59:59'; /* 今天 23:59:59 */

        $chk_re = $this->mod->select("
                                      SELECT
                                            `account`
                                      FROM
                                            `act_evt`
                                      WHERE
                                            `act_id` = ? AND
                                            `account`= ? AND
                                            `param1` = ? AND
                                            `itime`
                                      BETWEEN  '" . $today . "' AND '" . $today_end . "'",
            array($this->actInfo['id'], $acc, 'sing_in')
        );
        /* 確認當天是否有重複新增 END */

        /**
         * 假如有在資料庫裡 就 更新資料
         * 更新天數 ++
         */
        if (isset($mem_list[$acc])) {
            $mem_sin_list = $this->mod->select("SELECT
                                                      `account`,
                                                      `amount3` `sin_num` -- 簽到天數
                                                FROM
                                                      `act_evt`
                                                WHERE
                                                      `act_id` = ? AND
                                                      `account`= ? AND
                                                      `param1` = ?
                                               ",
                array($this->actInfo['id'], $acc, 'sing_in')
            );

            /* 判斷是否有重複更新 */
            $today = date("Y-m-d") . ' ' . '00:00:00'; /* 今天 00:00:00 */
            $today_end = date("Y-m-d") . ' ' . '23:59:59'; /* 今天 23:59:59 */

            $chk_utime = $this->mod->select("
                                             SELECT
                                                  `account`
                                             FROM
                                                  `act_evt`
                                             WHERE
                                                  `act_id` = ? AND
                                                  `account`= ? AND
                                                  `param1` = ? AND
                                                  `utime`
                                             BETWEEN  '" . $today . "' AND '" . $today_end . "'",
                array($this->actInfo['id'], $acc, 'sing_in')
            );
            /* 判斷是否有重複更新 END*/

            /* 判斷是否有重複更新 或 今天已增加 不能在更新 */
            if (empty($chk_utime) && empty($chk_re)) {
                foreach ($mem_sin_list as $k => $V) {
                    /* 更新會員簽到天數 */
                    $this->mod->modi_by('act_evt',
                        array(
                            'act_id' => $this->actInfo['id'], /* 活動代碼 */
                            'account' => $acc, /* 會員帳號 */
                            'param1' => 'sing_in' /* 簽到參數 */
                        ),
                        array(
                            'amount3' => $V['sin_num'] + 1, /* 簽到天數 */
                        )
                    );
                }
            }
        } else {
            /**
             * 如沒在資料庫 就新增
             * 判斷今日 有沒有重覆新增
             * 初始天數 1
             */
            if(empty($chk_re)) {
                $this->mod->add_by('act_evt',
                    array(
                        'act_id' => $this->actInfo['id'], /* 活動代碼 */
                        'account' => $acc, /* 會員帳號 */
                        'param1' => 'sing_in', /* 簽到參數 參數 */
                        'amount3' => '1', /* 簽到天數 */
                ));
            }
        }
        unset($mem_list[$acc]); /* 把變數 清掉 回傳 最新的陣列 */

        return $mem_list;
    }

    /* 抓取簽到會員資料 */
    public function get_mem_sin_in() {
        $mem_list = $this->mod->select("
                                        SELECT
                                              `account`, -- 會員帳號
                                              `amount3` `sin_num` -- 簽到天數
                                        FROM
                                              `act_evt`
                                        WHERE
                                              `act_id` = ? AND
                                              `param1` = ?
                                       ",
                                       array($this->actInfo['id'], 'sing_in')
        );

        $nary = array();
        foreach ($mem_list as $key=>$val) {
            $nary[$val['account']] = $val;
        }
        return $nary;
    }

    /**
     * 取得每日存款的會員 API
     * 如果有符合 並派發兌獎卷
     */
    public function mem_deposit_sing_in() {
        /* 抓取計算區間 ---------------------------------------------*/
        $deposit_day = $this->get_deposit_day(); /* 存款區間 */
        $deposit_range_start = $deposit_day[0]['deposit_start']; /* 開始時間 */
        $deposit_range_end = $deposit_day[0]['deposit_end']; /* 結束時間 */

        $deposit_range_end_add = $this->chang_time($deposit_range_end, '+1' , 'day');  /* 結束時間 再加一天 隔天會在更新最後一次 */

        $today = date("Y-m-d",time()); /* 今天 */
        $yes_day = date("Y/m/d", strtotime('-1 day')); /* 昨天 */
        $list = array();
        /* 抓取計算區間 End ------------------------------------------*/

        if($deposit_range_end_add >= $today && $deposit_range_start < $today) {
            $mem_list = $this->get_mem_deposit();

            $deposit = $this->dubai_api('getDeposByCountryDate', '1', $yes_day, $yes_day,'');
            if(isset($deposit['code']) && $deposit['code'] == 100) {
                $deposit_list = $deposit['list'];
                foreach ($deposit_list as $k=>$v) {
                    $mem_list = $this->set_mem_deposit($v['acc'], $mem_list);
                }

                /* 假如都是空的話 就整個資料刪除 */
                if(empty($deposit_list)) {
                    $this->mod->del_by('act_evt',
                        array(
                            'act_id' => $this->actInfo['id'], /* 活動代碼 */
                            'param1' => 'deposit'             /* 存款參數 */
                        ));
                }

                foreach ($mem_list as $k=>$v) {
                    $this->mod->del_by('act_evt',
                        array(
                            'act_id' => $this->actInfo['id'], /* 活動代碼 */
                            'param1' => 'deposit',            /* 存款參數 */
                            'account' => $k                   /* 會員帳號 */
                        ));
                    /* 刪除會員 */
                    $list[] = array(
                        'code' => 200,
                        'del_acc' => $k
                    );
                }

                $new_list = $this->get_mem_deposit(); /* 最新的簽到會員資料 */
                foreach ($new_list as $k=>$v) {
                    $mod = fmod($v['sin_num'], 2); /* 求餘數 */
                    if ($mod == '0') {
                        $num = 1; /* 獲得組數 */
                        $sin_day = 2;

                        /* 確認是否有重複新增 */
                        $today = date("Y-m-d") . ' ' . '00:00:00'; /* 今天 00:00:00 */
                        $today_end = date("Y-m-d") . ' ' . '23:59:59'; /* 今天 23:59:59 */

                        $chk_mem = $this->mod->select("SELECT
                                                            `account`
                                                       FROM
                                                            `act_evt`
                                                       WHERE
                                                            `act_id` = ? AND
                                                            `account`= ? AND
                                                            `param1` = ? AND
                                                            `param4` = ? AND
                                                            `itime`
                                                       BETWEEN  '" . $today . "' AND '" . $today_end . "'",
                            array($this->actInfo['id'], $v['account'], 'mem_ticket', 'deposit')
                        );

                        if (empty($chk_mem)) {
                            $this->mod->add_by('act_evt',
                                array(
                                    'act_id' => $this->actInfo['id'], /* 活動代碼 */
                                    'account' => $v['account'], /* 會員帳號 */
                                    'param1' => 'mem_ticket', /* 會員兌換數量 參數 */
                                    'param2' => $num, /* 未使用卷數  */
                                    'param3' => $sin_day, /* 連續簽到天數 */
                                    'param4' => 'deposit', /* 存款參數*/
                                    'descr1' => '連續簽到' . '-' . $sin_day, /* 備註 */
                                    'amount3' => $num /* 兌獎卷數量 */
                                ));
                            $list[] = array(
                                'code' => 100,
                                'add_acc' => $v['account']
                            );
                        }
                    }
                }
            }
        }

        $this->obj['code'] = 100;
        $this->obj['list'] = $list;
        $this->output();
    }

    /**
     * 設定存款會員資料
     * @param string $acc 每日存款會員
     * @param array $mem_list 會員存款 資料
     * @param return array
     */
    public function set_mem_deposit($acc, $mem_list) {
        /* 確認當天是否有重複新增 */
        $today = date("Y-m-d") . ' ' . '00:00:00';     /* 今天 00:00:00 */
        $today_end = date("Y-m-d") . ' ' . '23:59:59'; /* 今天 23:59:59 */

        $chk_re = $this->mod->select("SELECT
                                            `account`
                                      FROM
                                            `act_evt`
                                      WHERE
                                            `act_id` = ? AND
                                            `account`= ? AND
                                            `param1` = ? AND
                                            `itime`
                                      BETWEEN  '" . $today . "' AND '" . $today_end . "'",
            array($this->actInfo['id'], $acc, 'deposit')
        );
        /* 確認當天是否有重複新增 END */

        /**
         * 假如有在資料庫裡 就 更新資料
         * 更新天數 ++
         */
        if (isset($mem_list[$acc])) {
            $mem_sin_list = $this->mod->select("SELECT
                                                      `account`,
                                                      `amount3` `sin_num` -- 簽到天數
                                                FROM
                                                      `act_evt`
                                                WHERE
                                                      `act_id` = ? AND
                                                      `account`= ? AND
                                                      `param1` = ?
                                               ",
                array($this->actInfo['id'], $acc, 'deposit')
            );

            /* 判斷是否有重複更新 */
            $today = date("Y-m-d") . ' ' . '00:00:00'; /* 今天 00:00:00 */
            $today_end = date("Y-m-d") . ' ' . '23:59:59'; /* 今天 23:59:59 */

            $chk_utime = $this->mod->select("SELECT
                                                  `account`
                                             FROM
                                                  `act_evt`
                                             WHERE
                                                  `act_id` = ? AND
                                                  `account`= ? AND
                                                  `param1` = ? AND
                                                  `utime`
                                             BETWEEN  '" . $today . "' AND '" . $today_end . "'",
                array($this->actInfo['id'], $acc, 'deposit')
            );
            /* 判斷是否有重複更新 END*/

            /* 判斷是否有重複更新 或 今天已增加 不能在更新 */
            if (empty($chk_utime) && empty($chk_re)) {
                foreach ($mem_sin_list as $k => $V) {
                    /* 更新會員簽到天數 */
                    $this->mod->modi_by('act_evt',
                        array(
                            'act_id' => $this->actInfo['id'], /* 活動代碼 */
                            'account' => $acc, /* 會員帳號 */
                            'param1' => 'deposit' /* 簽到參數 */
                        ),
                        array(
                            'amount3' => $V['sin_num'] + 1, /* 簽到天數 */
                        )
                    );
                }
            }
        } else {
            /**
             * 如沒在資料庫 就新增
             * 判斷今日 有沒有重覆新增
             * 初始天數 1
             */
            if(empty($chk_re)) {
                $this->mod->add_by('act_evt',
                    array(
                        'act_id' => $this->actInfo['id'], /* 活動代碼 */
                        'account' => $acc, /* 會員帳號 */
                        'param1' => 'deposit', /* 簽到參數 參數 */
                        'amount3' => '1', /* 簽到天數 */
                    ));
            }
        }
        unset($mem_list[$acc]); /* 把變數 清掉 回傳 最新的陣列 */

        return $mem_list;
    }

    /**
     * 抓取存款會員資料
     * @param return array
     */
    public function get_mem_deposit() {
        $mem_list = $this->mod->select("
                                        SELECT
                                              `account`, -- 會員帳號
                                              `amount3` `sin_num` -- 簽到天數
                                        FROM
                                              `act_evt`
                                        WHERE
                                              `act_id` = ? AND
                                              `param1` = ?
                                       ",
            array($this->actInfo['id'], 'deposit')
        );

        $nary = array();
        foreach ($mem_list as $key=>$val) {
            $nary[$val['account']] = $val;
        }
        return $nary;
    }

    /* 取得計算存款開始時間 */
    public function get_deposit_day() {
        $sql = "
                SELECT
                      `date1` `deposit_start`,	 -- 存款開始時間
                      `date2` `deposit_end`     -- 存款結束時間
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ?
                LIMIT 1
		 ";
        $deposit_range = $this->mod->select($sql, array($this->actInfo['id'], 'deposit_range'));
        return $deposit_range;
    }

    /**
     * 前台登入 取得可使用彩卷數量
     * @param string $acc 會員帳號
     * @param number 數量
     */
    public function get_mem_tick_qry($acc) {
        $sql = "
                 SELECT
                      `param2` `no_use_ticket`, -- 未使用彩卷
                      `itime` `ticket_add_time` -- 兌獎卷新增時間
                 FROM
                      `act_evt`
                 WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `account` = ?
                ";

        $sql .= 'ORDER BY `itime` ASC';
        $mem_ticket_list = $this->mod->select($sql, array($this->actInfo['id'], 'mem_ticket', $acc));

        $today = date("Y-m-d H:i:s ",time()); /* 今天 */

        $num_effective = 0;
        foreach ($mem_ticket_list as $key=>$value) {
            /* 有效日期 */
            $effective_date_time = $this->chang_time($value['ticket_add_time'], '+2', 'day');
            $effective_date = substr($effective_date_time, '0', '10');
            $date =  $effective_date. ' '. '12:00:00';

            /* 累計有效票卷數 */
            $num_effective = ($date > $today) ? $num_effective += (int)$value['no_use_ticket'] : $num_effective;
        }
        return $num_effective;
    }

    /**
     * 累計中獎金額
     * @param string $acc 會員帳號
     * @param return array OR  ''
     */
    public function get_mem_bingo_amount($acc) {
        $sql = "
                SELECT
                     SUM(param5) AS `amount`
                FROM
                     `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `account` = ? AND 
                      `status1` = ?
                ";

        $sql .= 'ORDER BY `itime` ASC';
        $mem_bingo_amount = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $acc, '3'));

        $amount = !empty($mem_bingo_amount)?$mem_bingo_amount['0']['amount']:'';
        return $amount;
    }

    /* 前台顯示會員轉出日期　*/
    public function get_mem_history_turn_num() {
        $sql = "
                SELECT
                      DISTINCT DATE_FORMAT(`itime`, '%Y-%m') as `turn_time` -- 轉出時間
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `account` = ? 
                ORDER BY `itime` DESC
                ";

        $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $_POST['acc']));

        $option_turn_date = array();
        foreach ($mem_turn_list as $k=>$v) {

            $optionDate = date('m', strtotime($v['turn_time']));
            if (!isset($option_turn_date[$optionDate])) {
                $option_turn_date[] = array('dateId' => substr($v['turn_time'], 0, 10), 'date' => $optionDate);
            }
        }
        $option_turn_date_last = !empty($mem_turn_list)? date('Y-m', strtotime($mem_turn_list['0']['turn_time'])):'';

        if($option_turn_date_last == '') {
            $this->obj['code'] = 400;
            $this->output();
        }
        $this->obj['code'] = 100;
        $this->obj['option_turn_date'] = $option_turn_date;
        $this->obj['option_turn_date_last'] = $option_turn_date_last;
        $this->output();
    }

    /* 搜尋會員轉號碼歷史紀錄 */
    public function get_mem_turn_history() {
        if(!isset($_POST['turn_date']) || !isset($_POST['acc'])){
            //$this->add_error('get_mem_turn_history', '404', '會員轉號碼歷史紀錄-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

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

        /* 日期查詢 */
        if(isset($_POST['ticket_date'])) {
            $sql .= 'AND `itime` LIKE "'.$_POST['ticket_date'].'%"';
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
    }

    /* 我的彩球 */
    public function my_ball_num() {
        if(!isset($_POST['turn_time']) && !isset($_POST['acc'])){
            //$this->add_error('get_mem_turn_history', '404', '會員轉號碼歷史紀錄-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        $sql = "
                    SELECT
                        DISTINCT `itime` `turn_time`,
                        `param2` `order_per`,
                        `descr1` `number`, -- 開獎號碼
                        `descr2` `special_num` -- 特別號
                    FROM
                        `act_evt`
                    WHERE
                        `act_id` = ? AND 
                        `itime` = ? AND 
                        `account` = ?
                    ";
        $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], $_POST['turn_time'], $_POST['acc']));

        $mem_turn = array();
        foreach ($mem_turn_list as $k=>$v) {
            $date = $this->get_turn_date($v['order_per']); /* 取得期數的開獎日期 */
            $turn_date = substr($date['0']['lottery_date'], 0, 10);

            $data = array(
                'turn_time' => $v['turn_time'], /* 轉彩球時間 */
                'number' => $v['number'], /* 轉出號碼 */
                'turn_date' => $turn_date, /* 開獎日期 */
                'special_num' => $v['special_num'] /* 獎金是否存入狀態 */
            );
            $mem_turn[] = $data;
        }

        $this->obj['code'] = 100;
        $this->obj['list'] = $mem_turn;
        $this->output();

    }

    /* 我的兌獎紀錄 */
    public function get_turn_date_history() {
        if(!isset($_POST['turn_date']) || !isset($_POST['acc'])){
            $this->add_error('get_mem_turn_history', '404', '會員轉號碼歷史紀錄-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        $sql = "
                SELECT
                       `account` `turn_acc`, -- 會員帳號
                       `param2` `order_per`, -- 兌換期數
                       `param3` `result`, -- 兌獎結果
                       `descr1` `turn_num`, -- 轉出號碼
                       `descr2` `special_num`, -- 特別號
                       `itime` `turn_time`, -- 轉出時間
                       `date1` `give_time` -- 派彩時間 北京
                 FROM
                       `act_evt`
                 WHERE
                       `act_id` = ? AND 
                       `param1` = ? AND 
                       `account` = ? AND 
                       `itime` LIKE '".$_POST['turn_date']."%'
          ";
        $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $_POST['acc']));

        $mem_turn = array();
        foreach ($mem_turn_list as $k=>$v) {
            //$date = $this->get_turn_date($v['order_per']); /* 取得期數的開獎日期 */
            $turn_date = substr($v['turn_time'], 0, 10);
            $time = '0000-00-00';
            if($v['give_time'] != '0000-00-00 00:00:00') {
                $time = $v['give_time'];
            }

            $data = array(
                'turn_acc' => $v['turn_acc'], /* 會員帳號 */
                'order_per' => $v['order_per'], /* 兌換期數 */
                'result' => $v['result'], /* 兌獎結果 */
                'turn_num' => $v['turn_num'], /* 轉出號碼 */
                'special_num' => $v['special_num'], /* 特別號 */
                'turn_time' => $turn_date, /* 兌換日期 */
                'give_time' => substr($time , 0, 10) /* 派彩時間 */
            );
            $mem_turn[] = $data;
        }

        $this->obj['code'] = 100;
        $this->obj['mem_turn_list'] = $mem_turn;
        $this->output();
    }

    /**
     * 抓取上一期 如果有中獎結果
     * @param string $acc 會員帳號
     * @param array OR 0
     */
    public function get_last_term($acc) {
        if(!isset($acc)){
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        $sql = "
                SELECT
                       `date1` `give_time`, -- 派彩時間 北京
                       `itime` `turn_time` -- 轉出時間
                 FROM
                       `act_evt`
                 WHERE
                       `act_id` = ? AND 
                       `param1` = ? AND 
                       `account` = ? AND 
                       `status1` = ? AND 
                       `status2` = ? AND 
                       `amount3` = ? AND 
                       `date1` <> ?
                ORDER BY `itime` DESC
          ";
        $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $acc, '3', '2', '0', '0000-00-00 00:00:00'));

        return  $return = !empty($mem_turn_list)?$mem_turn_list['0']['turn_time']:'0';
    }

    /* 更新已點過 中獎資訊 */
    public function update_last_term() {
        if(!isset($_POST['acc'])){
            //$this->add_error('get_mem_turn_history', '404', '會員轉號碼歷史紀錄-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        $this->mod->modi_by('act_evt',
            array(
                'act_id' => $this->actInfo['id'], /* 活動代碼 */
                'account' => $_POST['acc'], /* 會員帳號 */
                'param1' => 'turn_number', /* 會員中獎 參數 */
                'status1' => '3', /* 3 = 已中獎 */
                'status2' => '2', /* 2 = 已派獎 */
                'amount3' => '0' /* 未點中獎資訊 */
            ),
            array(
                'amount3' => '1', /* 已點過 中獎資訊 */
            )
        );

        $this->obj['code'] = 100;
        $this->output();
    }

    /**
     * 計算已兌換組數
     * @param string $acc 會員帳號
     * @param return array
     */
    public function exchange_ball($acc) {
        $sql = "
                SELECT
                      count(descr1) `turn_num` -- 兌換組數
                 FROM
                      `act_evt`
                 WHERE
                       `act_id` = ? AND 
                       `param1` = ? AND 
                       `account` = ? 
          ";
        $exchange_ball = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $acc));
        $ball = !empty($exchange_ball)?$exchange_ball['0']['turn_num']:'0';

        return $ball;
    }

    /**
     * 取得期數資料 開獎日期 大於現在的日期  取最新一筆資料
     * 前台 倒數計時用
     */
    public function get_last_lottery() {
        $today = date("Y-m-d H:i:s"); /* 現在 */
        $start_time = $this->chang_time($today , '-1', 'hour'); /* 越南時間 台灣減去一小時 */

        $num_list = $this->get_last_lottery_qry($today);

        if(!empty($num_list['0'])) {
            $lottery_date = $num_list['0']['lottery_date'];
        } else {
            $lottery_date = '';
        }

        $num_last = !empty($num_list)?$num_list['0']:'';

        $this->obj['code'] = 100;
        $this->obj['list'] = $num_last; /* 回傳 最新一筆 */
        $this->obj['start_time'] = $today; /* 開始時間 */
        $this->obj['optionDate'] = $lottery_date; /* 開獎日期 */
        $this->output();
    }

    /**
     * 抓取最新一期的期數資料
     * @param date  $start_time 最新的時間
     * @param return array
     */
    public function get_last_lottery_qry($start_time) {
        $sql = "
                SELECT
                      `param2` `number_periods`, -- 期數
                      `date1` `lottery_date`	 -- 開獎日期
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `status1` = ? AND 
                      `descr1` = '' AND
                      `param5` = '' AND
                      `date1` >= ?
                ORDER BY `date1` ASC
          ";
        $num_list = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', '0', $start_time));

        return $num_list;
    }

    /* 抓取期數資料 已有開獎紀錄 查詢區間*/
    public function get_periods() {
        $sql = "
                SELECT
                      DISTINCT DATE_FORMAT(`date1`, '%Y-%m') as `lottery_month` -- 開獎日期
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND
                      `param1` = ? AND
                      `status1` = ? AND 
                      `status2` = ?
                ORDER BY `date1` DESC
		        ";
        $number_periods = $this->mod->select($sql, array($this->actInfo['id'], 'number_set','1', '1'));

        $option_lottery_date = array();
        foreach ($number_periods as $k=>$v) {

            $optionDate = date('m', strtotime($v['lottery_month']));
            if (!isset($option_lottery_date[$optionDate])) {
                $option_lottery_date[] = array('dateId' => substr($v['lottery_month'], 0, 7), 'date' => $optionDate);
            }
        }
        $periods_data_last = !empty($number_periods)? date('Y-m', strtotime($number_periods['0']['lottery_month'])):'';
        $this->obj['code'] = 100;
        $this->obj['option_lottery_date'] = $option_lottery_date;
        $this->obj['periods_data_last'] = $periods_data_last;
        $this->output();
    }

    /* 抓取 參加會員 搜尋的日期 */
    public function get_ticket_date() {
        $dateList = array();
        $timeStart = strtotime($this->actInfo['start_time']); /* 活動開始 */
        $timeEnd = strtotime($this->actInfo['end_time']); /* 活動結束 */

        /* 如果活動結束 就顯示 活動結束時間 */
        if ($timeEnd < time()) {
            $date = new DateTime($this->actInfo['end_time']);
        } else {
            $date = new DateTime();
        }

        /* 日期 大於活動開始前 到現在的時間 */
        do {
            $dateList[] = array('date' => $date->format('Y-m-d'));
            $date->modify('-1 day');
            $tmpTime = strtotime($date->format('Y-m-d 23:59:59'));
        } while ($tmpTime >= $timeStart);

        $this->gdata['dateList'] = $dateList;
    }

    /* 抓取選取的期數 號碼 */
    public function get_periods_data() {
        $sql = "
                SELECT
                      `param2` `number_periods`, -- 期數
                      `descr1` `winning_numbers`, -- 開獎號碼
                      `param5` `special_numbers`, -- 特別號
                      `date1` `lottery_date`	 -- 開獎日期
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `status1` = ? AND 
                      `status2` = ? 
		        ";

        /* 期數查詢 */
        if(!empty($_POST['num_periods'])) {
            $sql .= 'AND `date1` LIKE "'.$_POST['num_periods'].'%"';
        }
        $sql .= 'ORDER BY `date1` DESC';
        $periods_data = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', '1', '1'));

        $this->obj['code'] = 100;
        $this->obj['periods_data'] = $periods_data;
        $this->output();
    }

    /**
     * 取得轉出號碼的開獎日期
     * @param date $turn_date
     * @param array
     */
    public function get_turn_date($turn_date) {
        $sql = "
                SELECT
                      `date1` `lottery_date`	 -- 開獎日期
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `param2` = ? 
		        ";
        $date = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $turn_date));
        return $date;
    }

    /* 我的彩球清單 */
    public function my_ticket_list() {
        $sql = "
                SELECT
                      `account` `turn_acc`, -- 會員帳號
                      `param2` `order_per`, -- 兌換期數
                      `param3` `result`, -- 兌獎結果
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
            $data = array(
                'turn_acc' => $value['turn_acc'], /* 會員帳號 */
                'order_per' => $value['order_per'], /* 兌換期數 */
                'result' => $value['result'], /* 兌獎結果 */
                'turn_num' => $value['turn_num'], /* 轉出號碼 */
                'special_num' => $value['special_num'], /* 特別號 */
                'turn_time' => $value['turn_time'], /* 兌換日期 */
                'give_time' => $value['give_time'] /* 派彩時間 */
            );
            $turn_num[] = $data;
        }

        $this->obj['code'] = 100;
        $this->obj['list'] = $turn_num;
        $this->output();
    }

	/**
     * 新增資料
     * @param string $db 資料庫名稱
     * @param array $data 新增資料
     */
    protected function insert_data($db, $data) {
        $result = $this->mod->add_by($db, $data);
        if ($result['lid'] === false) {
            return 400;
        } else {
            return 100;
        }
    }

    /**
     * 會員基本資訊 Call API
     * @param string $acc 會員帳號
     * @param return array
     */
    public function get_acc($acc) {
        $chk_acc = trim($acc);
        if (!preg_match('/^[A-Za-z0-9]+$/', $chk_acc)) {
            return 400;
        }

        $chk_acc = $this->dubai_api('getAcc', $chk_acc);

        if(isset($chk_acc['code']) && !isset($chk_acc['code'])){
            return $chk_acc;
        } else {
            $this->add_error('get_acc', '404', '會員資訊API-getAcc');
        }
    }

    /**
     * 寫入錯誤訊息 log
     * @param string $method 涵示名稱
     * @param number $code 代碼
     * @param text $log 訊息
     */
    public function add_error($method, $code, $log) {
        if($method == null || $code == null || $log == null) return false;

        $data = array(
           'act_id' => $this->actInfo['id'],
           'param1' => 'error',
           'param2' => $code,
           'param3' => $method,
           'descr1' => $log
        );
        $this->insert_data('act_evt', $data);
    }

    /**
     *  轉換時間
     *  $number +- 數字
     *  $type 類型 月份 小時 日期
     */
    public function chang_time($time, $number, $type) {
        $time = new DateTime($time);
        $chang_time = $time->modify(''.$number.' '.$type.'');
        $chang_now_time = $chang_time->format('Y-m-d H:i:s');

        return $chang_now_time;
    }

    /* 比對開獎結果 設定  排程比對*/
    public function lottery_bingo_result() {
        $sql = "
                    SELECT
                          `param2` `number_periods`, -- 期數
                          `param5` `special_numbers`, -- 特別號
                          `descr1` `winning_numbers`, -- 開獎號碼
                          `date1` `lottery_date`,	 -- 開獎日期
                          `status1` `chk_open` -- 確認是否有key 号碼
                    FROM
                          `act_evt`
                    WHERE
                          `act_id` = ? AND 
                          `param1` = ? AND 
                          `status1` = ? AND 
                          `status2` = ? AND 
                          `descr1` != '' AND 
                          `param5` != ''
                ";
        $num_list = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', '1', '0'));

        foreach ($num_list as $k=>$v) {
            $this->lottery_ticket_bingo($v['number_periods'], $v['winning_numbers'], $v['special_numbers']);

            $this->mod->modi_by('act_evt',
                array(
                    'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                    'param1'  => 'number_set', /* 期數參數 */
                    'param2'  => $v['number_periods'], /* 期數 */
                    'param5'  => $v['special_numbers'], /* 特別號*/
                    'descr1'  => $v['winning_numbers'], /* 開獎號碼 */
                    'status1' => '1' /* 已有開獎號碼 參數 */
                ),
                array(
                    'status2'  => '1', /* 已比對過的期數 */
                )
            );
        }
        $this->obj['code'] = 100;
        $this->output();
    }

    /* 抓取要比對的期數 寫入開獎結果 */
    public function lottery_ticket_bingo($number_periods, $winning_numbers, $special_numbers){
        if(empty($number_periods) || empty($winning_numbers) || empty($special_numbers)) return false;

        $sql = "
                SELECT
                      `id`,
                      `account` `turn_acc`, -- 會員帳號
                      `param2` `order_per`, -- 兌換期數
                      `descr2` `special_num`, -- 特別碼
                      `descr1` `turn_num` -- 轉出號碼
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND
                      `param1` = ? AND
                      `param2` = ?
                ";

        $sql .= 'ORDER BY `itime` ASC';
        $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $number_periods));

        foreach ($mem_turn_list as $k=>$v) {
            $money = $this->get_bingo_money($winning_numbers, $v['turn_num'], $special_numbers, $v['special_num']);
            if($money > 0) {
                $this->mod->modi_by('act_evt',
                    array(
                        'id' => $v['id'], /* 主鍵 */
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'account' => $v['turn_acc'], /* 會員帳號 */
                        'param1'  => 'turn_number', /* 轉出號碼參數 */
                        'param2'  => $number_periods /* 期數 */
                    ),
                    array(
                        'param3'  => '中獎-'.$money, /* 兌獎結果 */
                        'param5'  => $money, /* 兌獎結果 */
                        'status1' => '3', /* 3 = 已中獎 */
                        'status2' => '1' /* 1 = 未派獎 */
                    )
                );
            } else if($money == 0){
                $this->mod->modi_by('act_evt',
                    array(
                        'id' => $v['id'], /* 主鍵 */
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'account' => $v['turn_acc'], /* 會員帳號 */
                        'param1'  => 'turn_number', /* 轉出號碼參數 */
                        'param2'  => $number_periods /* 期數 */
                    ),
                    array(
                        'param3'  => '未中奖', /* 兌獎結果 */
                        'param5'  => '',
                        'status1' => '2', /* 2 = 未中獎 */
                        'status2' => '0'
                    )
                );
            }
        }
    }

    /**
     * 計算會員得獎金額
     * 中『5个正码』 +『特码』 2888
     * 中『5个正码』彩金 888
     * 中『4个正码』+『特码』 688
     * 中『4个正码』 188
     * 中『3个正码』+『特码』 88
     * 中『3个正码』 28
     * @param array $winning_numbers 開獎號碼
     * @param array $user_winning_numbers 會員轉出號碼
     * @param number $special 特別號
     * @param number $user_special 會員轉出特別號
     */
    public function get_bingo_money($winning_numbers, $user_winning_numbers, $special, $user_special) {
        $money = 0;
        $normal = explode(',', $winning_numbers);
        if (count($normal) != 6) {
            return $money;
        }
        $user_normal = explode(',', $user_winning_numbers);
        if (count($user_normal) != 6) {
            return $money;
        }

        $bingo_normal = array_intersect($normal, $user_normal);
        $bingo_special = ($special == $user_special); /* 特別號 */

        if (count($bingo_normal) < 3) {
            return $money;
        }
        if (count($bingo_normal) >= 5) {
            $money = !empty($bingo_special) ? 2888:888;
        } else if (count($bingo_normal) == 4) {
            $money = !empty($bingo_special) ? 688:188;
        } else if (count($bingo_normal) == 3) {
            $money = !empty($bingo_special) ? 88:28;
        }
        return $money;
    }
}