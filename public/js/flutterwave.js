/* global location flw_payment_args jQuery*/
var amount = flw_payment_args.amount,
	cbUrl = flw_payment_args.cb_url,
	country = flw_payment_args.country,
	curr = flw_payment_args.currency,
	desc = flw_payment_args.desc,
	email = flw_payment_args.email,
	firstname = flw_payment_args.firstname,
	lastname = flw_payment_args.lastname,
	form = jQuery("#flw-pay-now-button"),
	p_key = flw_payment_args.p_key,
	txref = flw_payment_args.txnref,
	paymentOptions = flw_payment_args.payment_options,
	paymentStyle = flw_payment_args.payment_style,
	disableBarter = flw_payment_args.barter,
	redirect_url;

if (form) {
	form.on("click", function (evt) {
		evt.preventDefault();
		if (paymentStyle == "inline") {
			processPayment();
		} else {
			location.href = flw_payment_args.cb_url;
		}
	});
}

//switch country base on currency
switch (curr) {
	case "KES":
		country = "KE";
		break;
	case "GHS":
		country = "GH";
		break;
	case "ZAR":
		country = "ZA";
		break;
	case "TZS":
		country = "TZ";
		break;

	default:
		country = "NG";
		break;
}

var processPayment = function () {
	// setup payload
	var ravePayload = {
		amount: amount,
		country: country,
		currency: curr,
		custom_description: desc,
		tx_ref: txref,
		customer: {
			email: email,
			phone_number: "08000000000" || "",
			name: firstname + " " + lastname,
		},
		payment_options:
			"card,ussd,banktransfer,mpesa,barter,mobilemoneyzambia,mobilemoneyrwanda,mobilemoneyzambia,qr,mobilemoneyuganda,credit,payattitude,mobilemoneyfranco,paga,1voucher,mobilemoneytanzania",
		public_key: p_key,
		onclose: function () {},
		callback: function (response) {
			if (response.status == "successful") {
				// popup.close();
				redirectPost(
					cbUrl +
						"?tx_ref=" +
						response.tx_ref +
						"&transaction_id=" +
						response.transaction_id,
					response.tx
				);
			} else {
				alert(response.respmsg);
			}

			popup.close(); // close modal
		},
		customizations: {
			title: flw_payment_args.title,
			description: desc,
			logo: "",
		},
	};

	if (flw_payment_args.plan) {
		ravePayload.payment_plan = flw_payment_args.plan;
	}

	//check for subaccounts
	//check for paymentplan

	// add payload
	var popup = FlutterwaveCheckout(ravePayload);
};

var sendPaymentRequestResponse = function (res) {
	jQuery.post(cbUrl, res.tx).success(function (data) {
		var response = JSON.parse(data);
		redirect_url = response.redirect_url;
		setTimeout(redirectTo, 5000, redirect_url);
	});
};

//redirect function
var redirectPost = function (location, args) {
	// console.log(args);
	var form = "";
	jQuery.each(args, function (key, value) {
		// value = value.split('"').join('\"')
		form +=
			'<input type="hidden" name="' + key + '" value="' + value + '">';
	});
	jQuery('<form action="' + location + '" method="POST">' + form + "</form>")
		.appendTo(jQuery(document.body))
		.submit();
};

var redirectTo = function (url) {
	location.href = url;
};
