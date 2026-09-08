"use client";

import { useEffect, useRef, useState } from "react";
import "swagger-ui-dist/swagger-ui.css";
import { centralBankSpec } from "@/lib/openapi/central-bank";
import { connectorSpec } from "@/lib/openapi/connector";
import { gatewaySpec } from "@/lib/openapi/gateway";
import { walletSpec } from "@/lib/openapi/wallet";

const documents = {
  gateway: { label: "API Gateway", spec: gatewaySpec },
  centralBank: { label: "Central Bank Core", spec: centralBankSpec },
  wallet: { label: "Wallet Service", spec: walletSpec },
  connector: { label: "Connector Service", spec: connectorSpec },
} as const;

type DocumentKey = keyof typeof documents;

export function SwaggerDocs() {
  const [selected, setSelected] = useState<DocumentKey>("centralBank");
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    let disposed = false;

    void import("swagger-ui-dist/swagger-ui-bundle.js").then(({ default: SwaggerUIBundle }) => {
      if (disposed || !containerRef.current) return;

      containerRef.current.replaceChildren();
      SwaggerUIBundle({
        domNode: containerRef.current,
        spec: documents[selected].spec,
        deepLinking: true,
        displayRequestDuration: true,
        filter: true,
        persistAuthorization: true,
        tryItOutEnabled: true,
      });
    });

    return () => {
      disposed = true;
      containerRef.current?.replaceChildren();
    };
  }, [selected]);

  return (
    <main className="api-docs-page">
      <header className="api-docs-header">
        <div>
          <strong>SmartBank API Explorer</strong>
          <span>OpenAPI untuk seluruh service</span>
        </div>
        <label>
          <span>Pilih server</span>
          <select value={selected} onChange={(event) => setSelected(event.target.value as DocumentKey)}>
            {Object.entries(documents).map(([key, document]) => (
              <option key={key} value={key}>{document.label}</option>
            ))}
          </select>
        </label>
      </header>
      <div ref={containerRef} />
    </main>
  );
}
