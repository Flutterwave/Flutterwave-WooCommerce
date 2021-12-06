/**
 * WordPress dependencies
 */
import { Button, Text } from "@wordpress/components";
import { safeDecodeURIComponent } from "@wordpress/url";
/**
 * Woocommerce dependencies
 */
import { Spinner, AbbreviatedCard } from "@woocommerce/components";
/**
 * Internal dependencies
 */
import InfoBox from "./components/InfoBox";
import PaymentMethods from "./components/PaymentMethods";
import PaymentLogos from "./icons";

const {
	card,
	Paypal,
	Momo,
	Migo,
	Pwbt,
	Barter,
	Paga,
	Qr,
	Ussd,
	Bank,
	Voucher,
	Payattitude,
} = PaymentLogos;
import React, { Fragment } from "react";
const Overview = () => {
	const hasWindow = typeof window !== "undefined";
	const width = hasWindow ? window.innerWidth : null;
	const height = hasWindow ? window.innerHeight : null;
	const Heading_1 = "Enable The best payments experience for your customers";
	const p2 =
		"The Flutterwave plugin integration is the one platform that lets you sell wherever your customers are online, in‑person, another part of the world, another currency and everywhere in‑between.";
	const Heading_2 = "Why Flutterwave?";
	const label = "Finish Setup";
	return (
		<Fragment>
			<div className="flutterwave-container">
				<div className="flutterwave-body">
					{/* top */}
					<div
						className="flutterwave-top"
						style={{ marginBottom: "48px" }}
					>
						<h3 className="flutterwave-h">{Heading_1}</h3>
						<p className="flutterwave-p">{p2}</p>
						{/* <Text numberOfLines={2} truncate>
							{p2}
						</Text> */}
					</div>

					<hr />
					{/* middle */}
					<div
						className="flutterwave-middle"
						style={{ marginTop: "67px", marginBottom: "95px" }}
					>
						<h3 className="flutterwave-h">{Heading_2}</h3>
						<Info />
					</div>
					<hr />
					{/* bottom */}
					<div
						className="flutterwave-bottom"
						style={{ marginTop: "56px" }}
					>
						<p style={{ marginBottom: "40px", fontSize: "18px" }}>
							By clicking “Finish setup”, you agree to the
							<a style={{ marginLeft: "3px", color: "#f5a623" }}>
								Terms of Service.
							</a>
						</p>
						<Button
							className="flutterwave-btn"
							variant="primary"
							style={{
								backgroundColor: "#f5a623",
								marginBottom: "20px",
							}}
							href={safeDecodeURIComponent(
								"admin.php?page=wc-admin&path=%2Fpayments%2Fsettings"
							)}
						>
							{label}
						</Button>
						{/* 
						<AbbreviatedCard
							title="Store Performance"
							description="Key performance metrics"
						>
							<p>Your stuff in a Card.</p>
						</AbbreviatedCard> */}
					</div>
				</div>
			</div>
		</Fragment>
	);
};

const Info = () => {
	return (
		<>
			<div style={{ display: "flex" }}>
				<div>
					<InfoBox
						title={"Fast and Seamless"}
						text={
							"Our highly responsive, quick and smart checkout means higher transaction success rates for you and less waiting time for your customers."
						}
					/>
					<InfoBox
						title={"Various Payment Methods"}
						text={
							"Flutterwave Checkout is integrated with your customers’ preferred payment method which means they can pay you how they want."
						}
					/>
					<InfoBox
						title={"Pay from anywhere"}
						text={
							"Convenience is key, so customers can make payments on mobile, desktop, through Visa QR etc and you can receive payments from customers anywhere they are in the world."
						}
					/>
				</div>

				<div
					style={{
						marginLeft: "61px",
						padding: "16px 14px",
						display: "flex",
					}}
				>
					<div>
						<PaymentMethods label="Card" icon={card} />
						<PaymentMethods label="Paypal" icon={Paypal} />
						<PaymentMethods label="USSD" icon={Ussd} />
						<PaymentMethods label="MoMo" icon={Momo} />
						<PaymentMethods label="Bank" icon={Bank} />
						<PaymentMethods label="Bank Transfer" icon={Pwbt} />
					</div>

					<div>
						<PaymentMethods label="Barter" icon={Barter} />
						<PaymentMethods label="Qr" icon={Qr} />
						<PaymentMethods label="Voucher" icon={Voucher} />
						<PaymentMethods label="Paga" icon={Paga} />
						<PaymentMethods label="" icon={Payattitude} />
						<PaymentMethods label="Migo" icon={Migo} />
					</div>
				</div>
			</div>
		</>
	);
};

export default Overview;
