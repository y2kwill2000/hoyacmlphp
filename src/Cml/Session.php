<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 13-8-9 下午2:22
 * *********************************************************** */

namespace Cml;

/**
 * Session保存位置處理類。封裝了Session入庫/Cache的邏輯處理
 * 採用Mysql存儲時需要建數據表,語句如下:
 * CREATE TABLE `cml_session` (
 * `id` char(32) NOT NULL,
 * `value` varchar(5000) NOT NULL,
 * `ctime` int(11) unsigned NOT NULL,
 * PRIMARY KEY(`id`)
 * )ENGINE=MEMORY DEFAULT CHARSET=utf8;
 *
 * @package Cml
 */
class Session
{
	/**
	 * session超時時間
	 *
	 * @var int $lifeTime
	 */
	private $lifeTime;

	/**
	 * \Cml\Db\Mysql\Pdo || Cml\Cache\File
	 *
	 * @var \Cml\Db\MySql\Pdo || \Cml\Cache\File $handler
	 */
	private $handler;

	/**
	 * 初始化
	 *
	 */
	public static function init()
	{
		$cmlSession = new Session();
		$cmlSession->lifeTime = ini_get('session.gc_maxlifetime');
		if (Config::get('session_user_loc') == 'db') {
			$cmlSession->handler = Model::getInstance()->db();
		} else {
			$cmlSession->handler = Model::getInstance()->cache();
		}
		ini_set('session.save_handler', 'user');
		session_module_name('user');
		session_set_save_handler(
			[$cmlSession, 'open'], //運行session_start()時執行
			[$cmlSession, 'close'], //在腳本執行結束或調用session_write_close(),或session_destroy()時被執行，即在所有session操作完後被執行
			[$cmlSession, 'read'], //在執行session_start()時執行，因為在session_start時會去read當前session數據
			[$cmlSession, 'write'], //此方法在腳本結束和session_write_close強制提交SESSION數據時執行
			[$cmlSession, 'destroy'], //在執行session_destroy()時執行
			[$cmlSession, 'gc'] //執行概率由session.gc_probability和session.gc_divisor的值決定，時機是在open,read之後，session_start會相繼執行open,read和gc
		);
		ini_get('session.auto_start') || session_start(); //自動開啟session,必須在session_set_save_handler後面執行
	}

	/**
	 * session open
	 *
	 * @param string $savePath
	 * @param string $sessionName
	 *
	 * @return bool
	 */
	public function open($savePath, $sessionName)
	{
		return true;
	}

	/**
	 * session close
	 *
	 * @return bool
	 */
	public function close()
	{
		if (Config::get('session_user_loc') == 'db') {
			$this->handler->wlink = null;
		}
		//$GLOBALS['debug'] && \Cml\Debug::stopAndShowDebugInfo(); 開啟ob_start()的時候 php此時已經不能使用壓縮，所以這邊輸出的數據是沒壓縮的，而之前已經告訴瀏覽器數據是壓縮的，所以會導致火狐、ie不能正常解壓
		//$this->gc($this->lifeTime);
		return true;
	}

	/**
	 * session讀取
	 *
	 * @param string $sessionId
	 *
	 * @return array|null
	 */
	public function read($sessionId)
	{

		if (Config::get('session_user_loc') == 'db') {
			$result = $this->handler->get(Config::get('session_user_loc_table') . '-id-' . $sessionId, true, true, Config::get('session_user_loc_tableprefix'));
			return $result ? $result[0]['value'] : null;
		} else {
			$result = $this->handler->get(Config::get('session_user_loc_tableprefix') . Config::get('session_user_loc_table') . '-id-' . $sessionId);
			return $result ? $result : null;
		}
	}

	/**
	 * session 寫入
	 *
	 * @param string $sessionId
	 * @param string $value
	 *
	 * @return bool
	 */
	public function write($sessionId, $value)
	{
		if (Config::get('session_user_loc') == 'db') {
			$this->handler->set(Config::get('session_user_loc_table'), [
				'id' => $sessionId,
				'value' => $value,
				'ctime' => Cml::$nowTime
			], Config::get('session_user_loc_tableprefix'));
		} else {
			$this->handler->set(Config::get('session_user_loc_tableprefix') . Config::get('session_user_loc_table') . '-id-' . $sessionId, $value, $this->lifeTime);
		}
		return true;
	}

	/**
	 * session 銷毀
	 *
	 * @param string $sessionId
	 *
	 * @return bool
	 */
	public function destroy($sessionId)
	{
		if (Config::get('session_user_loc') == 'db') {
			$this->handler->delete(Config::get('session_user_loc_table') . '-id-' . $sessionId, true, Config::get('session_user_loc_tableprefix'));
		} else {
			$this->handler->delete(Config::get('session_user_loc_tableprefix') . Config::get('session_user_loc_table') . '-id-' . $sessionId);
		}
		return true;
	}

	/**
	 * session gc回收
	 *
	 * @param int $lifeTime
	 *
	 * @return bool
	 */
	public function gc($lifeTime = 0)
	{
		if (Config::get('session_user_loc') == 'db') {
			$lifeTime || $lifeTime = $this->lifeTime;
			$this->handler->whereLt('ctime', Cml::$nowTime - $lifeTime)
				->delete(Config::get('session_user_loc_table'), true, Config::get('session_user_loc_tableprefix'));
		} else {
			//cache 本身會回收
		}
		return true;
	}
}
