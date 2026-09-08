declare module "swagger-ui-dist/swagger-ui-bundle.js" {
  interface SwaggerUIOptions {
    domNode: Element;
    spec: object;
    deepLinking?: boolean;
    displayRequestDuration?: boolean;
    filter?: boolean | string;
    persistAuthorization?: boolean;
    tryItOutEnabled?: boolean;
  }

  const SwaggerUIBundle: (options: SwaggerUIOptions) => unknown;
  export default SwaggerUIBundle;
}
