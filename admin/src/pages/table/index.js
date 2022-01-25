import React, { useState, useEfect } from "react";
import moment from "moment";
import {
	Table,
	Thead,
	Tbody,
	Tfoot,
	Tr,
	Th,
	Td,
	TableCaption,
	Tag,
} from "@chakra-ui/react";

const TableComp = ({ head, data, type }) => {
	// console.log(data);
	return (
		<React.Fragment>
			<Table class="flw-table" variant="simple">
				<Thead bg="#F4F6F8">
					<Tr>
						{head.map((h, index) => {
							return <Th key={index}>{h}</Th>;
						})}
					</Tr>
				</Thead>
				<Tbody>
					{type == "transactions" &&
						data.map((item, index) => (
							<Tr key={index}>
								<Td>{item.customer.email}</Td>
								<Td>{item.amount}</Td>
								<Td>{item.customer.name}</Td>
								<Td>
									{"successful" == item.status && (
										<Tag
											className="flw-tag"
											style={{
												backgroundColor: "#c6f6d5",
												color: "black",
												fontSize: "13px",
												fontWeight: "300",
											}}
										>
											{item.status}
										</Tag>
									)}
									{"failed" == item.status && (
										<Tag
											className="flw-tag"
											colorScheme="red"
										>
											{item.status}
										</Tag>
									)}
								</Td>
								<Td>
									{moment(new Date(item.created_at)).format(
										"MMMM Do YYYY, h:mm a"
									)}
								</Td>
							</Tr>
						))}

					{type == "plans" &&
						data.map((item, index) => (
							<Tr key={index}>
								<Td>{item.name}</Td>
								<Td>{item.amount}</Td>
								<Td>{item.duration}</Td>
								<Td>{item.interval}</Td>
								<Td>{item.currency}</Td>
								<Td>{item.status}</Td>
							</Tr>
						))}

					{type == "subaccounts" &&
						data.map((item, index) => (
							<Tr key={index}>
								<Td>{item.subaccount_id}</Td>
								<Td>{item.business_name}</Td>
								<Td>{item.bank_name}</Td>
								<Td>{item.country}</Td>
							</Tr>
						))}
				</Tbody>
			</Table>
		</React.Fragment>
	);
};

export default TableComp;
