import * as React from 'react';
import Accordion from '@mui/material/Accordion';
import AccordionDetails from '@mui/material/AccordionDetails';
import AccordionSummary from '@mui/material/AccordionSummary';
import Box from '@mui/material/Box';
import Container from '@mui/material/Container';
import Typography from '@mui/material/Typography';

import ExpandMoreIcon from '@mui/icons-material/ExpandMore';

export default function FAQ() {
    const [expanded, setExpanded] = React.useState<string | false>(false);

    const handleChange =
        (panel: string) => (event: React.SyntheticEvent, isExpanded: boolean) => {
            setExpanded(isExpanded ? panel : false);
        };

    return (
        <Container
            id="faq"
            sx={{
                pt: {xs: 4, sm: 12},
                pb: {xs: 8, sm: 16},
                position: 'relative',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                gap: {xs: 3, sm: 6},
            }}
        >
            <Typography
                component="h2"
                variant="h4"
                sx={{
                    color: 'text.primary',
                    width: {sm: '100%', md: '60%'},
                    textAlign: {sm: 'left', md: 'center'},
                }}
            >
                Frequently asked questions
            </Typography>
            <Box sx={{width: '100%'}}>
                <Accordion
                    expanded={expanded === 'panel1'}
                    onChange={handleChange('panel1')}
                >
                    <AccordionSummary
                        expandIcon={<ExpandMoreIcon/>}
                        aria-controls="panel1d-content"
                        id="panel1d-header"
                    >
                        <Typography component="h3" variant="subtitle2">
                            May I reuse the data obtained from Domain Watchdog?
                        </Typography>
                    </AccordionSummary>
                    <AccordionDetails>
                        <Typography
                            variant="body2"
                            gutterBottom
                            sx={{maxWidth: {sm: '100%', md: '70%'}}}
                        >
                            Although the source code of this project is open source, the license does not
                            extend to the data collected by it.<br/>
                            This data is redistributed under the same conditions as when it was obtained.
                            This means that you must respect the reuse conditions of each of the RDAP servers used.<br/>
                            <br/>
                            For each domain, Domain Watchdog tells you which RDAP server was contacted. <b>It is your
                            responsibility to check the conditions of use of this server.</b>
                        </Typography>
                    </AccordionDetails>
                </Accordion>
                <Accordion
                    expanded={expanded === 'panel2'}
                    onChange={handleChange('panel2')}
                >
                    <AccordionSummary
                        expandIcon={<ExpandMoreIcon/>}
                        aria-controls="panel2d-content"
                        id="panel2d-header"
                    >
                        <Typography component="h3" variant="subtitle2">
                            What is an RDAP server?
                        </Typography>
                    </AccordionSummary>
                    <AccordionDetails>
                        <Typography
                            variant="body2"
                            gutterBottom
                            sx={{maxWidth: {sm: '100%', md: '70%'}}}
                        >
                            The latest version of the WHOIS protocol was standardized in 2004 by RFC 3912 This
                            protocol allows anyone to retrieve key information concerning a domain name, an IP address,
                            or an entity registered with a registry.<br/>
                            <br/>
                            ICANN launched a global vote in 2023 to propose replacing the WHOIS protocol with RDAP. As a
                            result, registries and registrars will no longer be required to support WHOIS from 2025
                            (WHOIS Sunset Date).<br/>
                            <br/>
                            Domain Watchdog uses the RDAP protocol, which will soon be the new standard for retrieving
                            information concerning domain names.
                        </Typography>
                    </AccordionDetails>
                </Accordion>
                <Accordion
                    expanded={expanded === 'panel3'}
                    onChange={handleChange('panel3')}
                >
                    <AccordionSummary
                        expandIcon={<ExpandMoreIcon/>}
                        aria-controls="panel3d-content"
                        id="panel3d-header"
                    >
                        <Typography component="h3" variant="subtitle2">
                            What are Domain Watchdog's data sources?
                        </Typography>
                    </AccordionSummary>
                    <AccordionDetails>
                        <Typography
                            variant="body2"
                            gutterBottom
                            sx={{maxWidth: {sm: '100%', md: '70%'}}}
                        >
                            This project relies on open access data.
                            Domain Watchdog uses the RDAP protocol, which will soon be the new standard for retrieving
                            information concerning domain names.
                        </Typography>
                    </AccordionDetails>
                </Accordion>
                <Accordion
                    expanded={expanded === 'panel4'}
                    onChange={handleChange('panel4')}
                >
                    <AccordionSummary
                        expandIcon={<ExpandMoreIcon/>}
                        aria-controls="panel4d-content"
                        id="panel4d-header"
                    >
                        <Typography component="h3" variant="subtitle2">
                            What is the added value of Domain Watchdog rather than doing RDAP queries yourself?
                        </Typography>
                    </AccordionSummary>
                    <AccordionDetails>
                        <Typography
                            variant="body2"
                            gutterBottom
                            sx={{maxWidth: {sm: '100%', md: '70%'}}}
                        >
                            Although the RDAP and WHOIS protocols allow you to obtain precise information about a
                            domain, it is not possible to perform a reverse search to discover a list of domain names
                            associated with an entity. Additionally, accessing a detailed history of events (ownership
                            changes, renewals, etc.) is not feasible with these protocols.
                        </Typography>
                    </AccordionDetails>
                </Accordion>
                <Accordion
                    expanded={expanded === 'panel5'}
                    onChange={handleChange('panel5')}
                >
                    <AccordionSummary
                        expandIcon={<ExpandMoreIcon/>}
                        aria-controls="panel5d-content"
                        id="panel5d-header"
                    >
                        <Typography component="h3" variant="subtitle2">
                            Under what license is the source code for this project released?
                        </Typography>
                    </AccordionSummary>
                    <AccordionDetails>
                        <Typography
                            variant="body2"
                            gutterBottom
                            sx={{maxWidth: {sm: '100%', md: '70%'}}}
                        >
                            This entire project is licensed under GNU Affero General Public License v3.0 or later.
                            The source code is published on GitHub and freely accessible.
                        </Typography>
                    </AccordionDetails>
                </Accordion>
            </Box>
        </Container>
    );
}
