import AppShell from "@/components/AppShell";
import RolePage from "@/components/RolePage";
import AdminAudit from "@/components/dashboards/AdminAudit";

export default function AdminAuditPage() {
  return <AppShell><RolePage allowed={["ADMIN", "CENTRAL_BANK_ADMIN"]}><AdminAudit /></RolePage></AppShell>;
}
