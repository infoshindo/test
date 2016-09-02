SEPATH') OR exit('No direct script access allowed');

/*
 * @brief  | -----
 * @param  | -----
 * @return | -----
 */

class Apply_form extends CI_Controller {
	private $user_agent;
	private $data;

	public function __construct() {
		parent::__construct();
		$this->load->library('setup_front');
		$this->load->model('front/front_applications_model');
		$this->load->model('front/front_users_model');
		$this->data = $this->setup_front->common_data();
	}

	public function index() {
		$this->data["post"] = $this->input->post(NULL, TRUE);
		if ( empty($this->data["post"]["shop_id"]) ) show_404();

		# 店舗情報の読み込み
		$this->data["shops"] = $this->shops_model->select_shop_plural($this->data["post"]["shop_id"]);

		# viewの読み込み
		$this->data["view"] = $this->view->front_main($this->data["user_agent"], $this->data); // headerなどの共通view

		if( ! empty($this->data["post"]["apply_plural"]) )
		{
			$this->load->view("front/".$this->data["user_agent"]. "/apply_form/index", $this->data);
		}
		else
		{
			if( $this->form_validation->run("apply_form") && isset($this->data["post"]["appli"]) )
			{
				/*
				 | 応募完了
				*/

				# 会員登録 過去に登録が無ければ非会員でinsert
				$user_data = $this->front_users_model->insert_applicant($this->data['post']);

				# applicationsテーブルへインサート
				$this->front_applications_model->insert_front_plural($user_data, $this->data["user_agent"]);

				# メール送信
				$this->load->library( array("mail", "mail_template") );
				$user_data = array_merge($this->data["post"], $user_data);

				foreach ($this->data["shops"] as $key => $shop)
				{
					# 店舗&admin通知メール
					$mail_template = $this->mail_template->front_apply_shop($user_data, $shop); // 送信内容を取得
					$this->mail->front_apply_admin($mail_template); // adminへ通知メールを送信
					$this->mail->front_apply_shop($mail_template, $shop); // 店舗へ通知メールを送信

					# 会員へサンクスメールの送信
					$mail_template = $this->mail_template->front_apply_user($user_data, $shop); // 送信内容を取得
					$this->mail->front_apply_user($mail_template, $user_data); // ユーザーへメール送信
				}

				$this->load->view("front/".$this->data["user_agent"]. "/apply_form/thanks", $this->data);
			}
			elseif ( $this->form_validation->run("mypage/apply_form") && ! isset($this->data["post"]["back"]) )
			{
				# 確認ページ
				$this->load->view("front/".$this->data["user_agent"]. "/apply_form/confirm", $this->data);
			}
			else
			{
				$this->load->view("front/".$this->data["user_agent"]. "/apply_form/index", $this->data);
			}
		}
	}
}

