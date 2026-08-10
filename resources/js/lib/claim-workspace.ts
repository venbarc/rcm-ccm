export interface ClaimWorkspaceConfig {
    isPrinciple: boolean;
    identifierLabel: string;
    patientLabel: string;
    patientIdLabel: string;
    patientDobLabel: string;
    payerLabel: string;
    providerLabel: string;
    locationLabel: string;
    serviceDateLabel: string;
    procedureLabel: string;
    procedureLinesLabel: string;
    paymentsLabel: string;
    showTrueBalance: boolean;
    showModMed: boolean;
    showInvoiceFields: boolean;
    showInvoiceSummary: boolean;
    workProgressBasis: 'amount' | 'count';
    expectedImportColumns: string[];
}

const TRICITY_COLUMNS = [
    'CPT',
    'Location',
    'Bill ID',
    'Invoice Rate Per Unit',
    'CF Invoice Amount',
    'Payments',
    'True Balance',
    'True Charge',
    'Units',
    'BillingID-CPT',
    'Charges',
    'ModMed_Claim_Status',
    'CF Invoice Date',
    'Patient DOB',
    'Patient MRN',
    'Patient First Name',
    'Patient Last Name',
    'Payer',
    'Payer-CPT',
    'Place of Service Code',
    'Posted Date Month/Year',
    'Primary Provider',
    'Service Date',
    'True Charge Per Unit',
];

const PRINCIPLE_COLUMNS = [
    'Primary Claim ID',
    'Patient Name',
    'Patient Date of Birth',
    'Chart Number',
    'Responsible Payer',
    'Rendering Provider',
    'Location Name',
    'Date of Service',
    'Procedure Code',
    'Units',
    'Charge Amount',
    'CF Invoice Amount',
    'Total Payment',
    'True Charge',
    'True Charge Per Unit',
    'Insurance Balance',
    'Patient Balance',
    'Total Balance',
    'claim-cpt',
];

export function claimWorkspace(accountValue: string | null | undefined): ClaimWorkspaceConfig {
    const isPrinciple = accountValue === 'principle_spine_and_pain';

    return {
        isPrinciple,
        identifierLabel: isPrinciple ? 'Primary Claim ID' : 'Bill ID',
        patientLabel: isPrinciple ? 'Patient Name' : 'Patient',
        patientIdLabel: isPrinciple ? 'Chart Number' : 'Patient MRN',
        patientDobLabel: isPrinciple ? 'Patient Date of Birth' : 'Patient DOB',
        payerLabel: isPrinciple ? 'Responsible Payer' : 'Payer',
        providerLabel: isPrinciple ? 'Rendering Provider' : 'Primary Provider',
        locationLabel: isPrinciple ? 'Location Name' : 'Location',
        serviceDateLabel: isPrinciple ? 'Date of Service' : 'Service Date',
        procedureLabel: isPrinciple ? 'Procedure Code' : 'CPT Code',
        procedureLinesLabel: isPrinciple ? 'Procedure Lines' : 'CPT Lines',
        paymentsLabel: isPrinciple ? 'Total Payment' : 'Payments',
        showTrueBalance: !isPrinciple,
        showModMed: !isPrinciple,
        showInvoiceFields: !isPrinciple,
        showInvoiceSummary: true,
        workProgressBasis: isPrinciple ? 'count' : 'amount',
        expectedImportColumns: isPrinciple ? PRINCIPLE_COLUMNS : TRICITY_COLUMNS,
    };
}
