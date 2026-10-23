<?php
defined('ABSPATH') || exit;

class MN_Email_Customer_Order_Status extends WC_Email {
    private $default_subject = '';
    private $default_heading = '';
    private $intro = '';

    public function __construct($config) {
        $this->id = $config['id'];
        $this->customer_email = true;
        $this->title = $config['title'];
        $this->email_group = 'order-updates';
        $this->template_html = 'emails/customer-order-status.php';
        $this->template_plain = 'emails/plain/customer-order-status.php';
        $this->default_subject = $config['subject'];
        $this->default_heading = $config['heading'];
        $this->intro = $config['intro'];
        $this->placeholders = [
            '{order_date}' => '',
            '{order_number}' => '',
        ];

        foreach ($config['hooks'] as $hook) {
            add_action($hook, [$this, 'trigger'], 10, 2);
        }

        parent::__construct();
        $this->description = $config['description'];
    }

    public function get_default_subject() {
        return $this->default_subject;
    }

    public function get_default_heading() {
        return $this->default_heading;
    }

    public function get_default_additional_content() {
        return 'Questions about this order? Reply to this email and we will help.';
    }

    public function trigger($order_id, $order = false) {
        $this->setup_locale();

        if ($order_id && !is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }

        if (is_a($order, 'WC_Order')) {
            $this->object = $order;
            $this->recipient = $order->get_billing_email();
            $this->placeholders['{order_date}'] = wc_format_datetime($order->get_date_created());
            $this->placeholders['{order_number}'] = $order->get_order_number();
        }

        $this->send_notification();
        $this->restore_locale();
    }

    public function get_content_html() {
        return wc_get_template_html($this->template_html, $this->template_args(false));
    }

    public function get_content_plain() {
        return wc_get_template_html($this->template_plain, $this->template_args(true));
    }

    private function template_args($plain_text) {
        return [
            'order' => $this->object,
            'email_heading' => $this->get_heading(),
            'additional_content' => $this->get_additional_content(),
            'intro' => $this->format_string($this->intro),
            'sent_to_admin' => false,
            'plain_text' => $plain_text,
            'email' => $this,
        ];
    }
}
