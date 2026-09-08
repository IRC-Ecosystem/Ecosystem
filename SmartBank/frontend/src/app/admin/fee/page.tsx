import AppShell from "@/components/AppShell";
import RolePage from "@/components/RolePage";
import AdminFee from "@/components/dashboards/AdminFee";

export default function AdminFeePage() {
  return <AppShell><RolePage allowed={["ADMIN", "CENTRAL_BANK_ADMIN"]}><AdminFee /></RolePage></AppShell>;
}
