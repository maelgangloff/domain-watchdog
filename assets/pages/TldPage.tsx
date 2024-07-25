import React, {useEffect, useState} from 'react';
import Container from "@mui/material/Container";
import {Accordion, AccordionDetails, AccordionSummary, Grid, Typography} from "@mui/material";
import {ExpandMore} from "@mui/icons-material";
import HeadTable from "../components/HeadTable";
import {getTldList} from "../utils/api";
import Footer from "../components/Footer";

const gTldColumns = [
    {id: 'tld', label: 'TLD'},
    {id: 'registryOperator', label: 'Operator'}
]

const sTldColumns = [
    {id: 'tld', label: 'TLD'}
]

const toEmoji = (tld: string) => String.fromCodePoint(
    ...getCountryCode(tld)
        .toUpperCase()
        .split('')
        .map((char) => 127397 + char.charCodeAt(0)
        )
)

const getCountryCode = (tld: string): string => {
    const exceptions = {uk: 'gb', su: 'ru', tp: 'tl'}
    if (tld in exceptions) return exceptions[tld as keyof typeof exceptions]
    return tld
}

const regionNames = new Intl.DisplayNames(['en'], {type: 'region'})

const ccTldColumns = [
    {id: 'tld', label: 'TLD'},
    {
        id: 'tld',
        label: 'Flag',
        format: (tld: string) => toEmoji(tld)
    },
    {id: 'tld', label: 'Country name', format: (tld: string) => regionNames.of(getCountryCode(tld)) ?? '-'},
]

export default function TldPage() {
    const [sTld, setSTld] = useState<any>([])
    const [gTld, setGTld] = useState<any>([])
    const [ccTld, setCcTld] = useState<any>([])
    const [brandGTld, setBrandGTld] = useState<any>([])

    useEffect(() => {
        getTldList({type: 'sTLD'}).then(setSTld)
        getTldList({type: 'gTLD', contractTerminated: 0, specification13: 0}).then(setGTld)
        getTldList({type: 'gTLD', contractTerminated: 0, specification13: 1}).then(setBrandGTld)
        getTldList({type: 'ccTLD'}).then(setCcTld)
    }, [])

    return (
        <Container maxWidth="lg" sx={{mt: 20, mb: 4}}>
            <Grid container spacing={3}>
                <Grid item xs={12} md={8} lg={9}>
                    <Accordion>
                        <AccordionSummary expandIcon={<ExpandMore/>}>
                            <Typography sx={{width: '33%', flexShrink: 0}}>
                                sTLD
                            </Typography>
                            <Typography sx={{color: 'text.secondary'}}>Sponsored Top-Level Domains</Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                            <HeadTable rows={sTld} columns={sTldColumns}/>
                        </AccordionDetails>
                    </Accordion>
                    <Accordion>
                        <AccordionSummary expandIcon={<ExpandMore/>}>
                            <Typography sx={{width: '33%', flexShrink: 0}}>
                                gTLD
                            </Typography>
                            <Typography sx={{color: 'text.secondary'}}>Generic Top-Level Domains</Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                            <HeadTable rows={gTld} columns={gTldColumns}/>
                        </AccordionDetails>
                    </Accordion>
                    <Accordion>
                        <AccordionSummary expandIcon={<ExpandMore/>}>
                            <Typography sx={{width: '33%', flexShrink: 0}}>
                                Brand gTLD
                            </Typography>
                            <Typography sx={{color: 'text.secondary'}}>Brand Generic Top-Level Domains</Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                            <HeadTable rows={brandGTld} columns={gTldColumns}/>
                        </AccordionDetails>
                    </Accordion>
                    <Accordion>
                        <AccordionSummary expandIcon={<ExpandMore/>}>
                            <Typography sx={{width: '33%', flexShrink: 0}}>
                                ccTLD
                            </Typography>
                            <Typography sx={{color: 'text.secondary'}}>Country-Code Top-Level Domains</Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                            <HeadTable rows={ccTld} columns={ccTldColumns}/>
                        </AccordionDetails>
                    </Accordion>
                </Grid>
            </Grid>
            <Footer/>
        </Container>
    );
};
