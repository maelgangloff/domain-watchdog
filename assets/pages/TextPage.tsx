import React from "react";
import snarkdown from "snarkdown"

export default function TextPage({markdown}: { markdown: string }) {
    return <div dangerouslySetInnerHTML={{__html: snarkdown(markdown)}}></div>
}