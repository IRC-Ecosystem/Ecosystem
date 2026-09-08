import AppShell from "@/components/AppShell";
import RolePage from "@/components/RolePage";
import AdminIssuance from "@/components/dashboards/AdminIssuance";

export default function AdminIssuancePage() {
  return <AppShell><RolePage allowed={["ADMIN", "CENTRAL_BANK_ADMIN"]}><AdminIssuance /></RolePage></AppShell>;
}