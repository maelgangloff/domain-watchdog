import {EventAction} from "../api";

export const actionToColor = (a: EventAction) => a === 'registration' ? 'green' :
    a === 'reregistration' ? 'cyan' :
        a === 'expiration' ? 'red' :
            a === 'deletion' ? 'magenta' :
                a === 'transfer' ? 'orange' :
                    a === 'last changed' ? 'blue' :
                        a === 'registrar expiration' ? 'red' :
                            a === 'reinstantiation' ? 'purple' :
                                a === 'enum validation expiration' ? 'red' : 'default'