import type { Metadata } from "next";
import { SwaggerDocs } from "./swagger-docs";
import "./styles.css";

export const metadata: Metadata = {
  title: "SmartBank API Docs",
  description: "Swagger UI untuk seluruh SmartBank API",
};

export default function ApiDocsPage() {
  return <SwaggerDocs />;
}
